<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Controller;

use OCA\IOIDC\Db\IOIDCProvider;
use OCA\IOIDC\Db\IOIDCProviderMapper;
use OCA\IOIDC\Db\IOIDCStateMapper;
use OCA\IOIDC\Db\IOIDCUserMapper;
use OCA\IOIDC\Service\OidcClient;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;
use UnexpectedValueException;

class ApiController extends Controller
{
    private string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        private IURLGenerator $urlGenerator,
        private IOIDCUserMapper $ioidcUserMapper,
        private IOIDCProviderMapper $ioidcProviderMapper,
        private IOIDCStateMapper $ioidcStateMapper,
        IUserSession $userSession,
        private LoggerInterface $logger,
        private OidcClient $oidcClient,
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userSession->getUser()?->getUID() ?? '';
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function callback(): RedirectResponse
    {
        $url = $this->urlGenerator->getAbsoluteURL('/index.php/settings/user/connected-accounts');
        $providerId = null;

        try {
            $params = $this->request->getParams();
            $state = $this->requestString($params, 'state');
            $transaction = $this->ioidcStateMapper->consume_state($this->userId, $state, time());
            $providerId = (int)$transaction['provider_id'];
            if (isset($params['error'])) {
                throw new UnexpectedValueException('Provider returned an authorization error');
            }
            $code = $this->requestString($params, 'code');

            $redirectUri = $this->urlGenerator->getAbsoluteURL('/index.php/apps/integration_oidc/callback');
            $token = $this->oidcClient->exchangeAuthorizationCode($transaction, $code, $redirectUri);
            $idToken = $token['id_token'];
            if (!is_string($idToken)) {
                throw new UnexpectedValueException('Token response did not contain an ID token');
            }
            $claims = $this->oidcClient->validateIdToken($idToken, $transaction, (string)$transaction['nonce_hash']);

            $refreshToken = $token['refresh_token'];
            if (!is_string($refreshToken) || $refreshToken === '') {
                throw new UnexpectedValueException('Token response did not contain a refresh token');
            }

            $this->ioidcUserMapper->register_user([
                'accessToken' => $token['access_token'],
                'email' => is_string($claims->email ?? null) ? $claims->email : null,
                'expiresIn' => $token['expires_in'],
                'providerId' => $providerId,
                'providerVersion' => (int)$transaction['config_version'],
                'refreshToken' => $refreshToken,
                'scope' => $token['scope'] !== '' ? $token['scope'] : (string)$transaction['scope'],
                'sub' => $claims->sub,
                'tokenType' => $token['token_type'],
                'timestamp' => time(),
                'uid' => $this->userId,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('OIDC callback rejected', [
                'providerId' => $providerId,
                'reason' => $e::class,
            ]);
        }

        return new RedirectResponse($url);
    }

    /**
     * @NoAdminRequired
     */
    public function query(): DataResponse
    {
        return new DataResponse($this->ioidcProviderMapper->query(), Http::STATUS_OK);
    }

    /**
     * @NoAdminRequired
     */
    public function queryUser(): DataResponse
    {
        $response = [];
        foreach ($this->ioidcUserMapper->query_user($this->userId) as $row) {
            $response[] = [
                'id' => (int)$row['id'],
                'provider_id' => (int)$row['provider_id'],
                'name' => (string)$row['name'],
                'requires_reauthorization' => (bool)($row['requires_reauthorization'] ?? false),
                'archived' => (bool)($row['archived'] ?? false),
            ];
        }

        return new DataResponse($response, Http::STATUS_OK);
    }

    public function register(): DataResponse
    {
        try {
            $params = $this->prepareProviderParams($this->request->getParams(), false);
            $id = $this->ioidcProviderMapper->register($params);

            return new DataResponse(['status' => 'success', 'id' => $id], Http::STATUS_OK);
        } catch (Throwable $e) {
            $this->logger->warning('OIDC provider registration rejected', ['reason' => $e::class]);

            return new DataResponse(['status' => 'error'], Http::STATUS_BAD_REQUEST);
        }
    }

    public function update(): DataResponse
    {
        try {
            $params = $this->request->getParams();
            $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                throw new UnexpectedValueException('Provider ID is invalid');
            }
            $entity = $this->ioidcProviderMapper->get($id);
            $params = $this->prepareProviderParams($params, true);
            if ($this->hasSecuritySensitiveChanges($entity, $params)) {
                $entity->setConfigVersion(max(1, (int)$entity->getConfigVersion()) + 1);
            }
            $entity->setParams($params, true);
            $this->ioidcProviderMapper->update($entity);

            return new DataResponse(['status' => 'success'], Http::STATUS_OK);
        } catch (Throwable $e) {
            $this->logger->warning('OIDC provider update rejected', ['reason' => $e::class]);

            return new DataResponse(['status' => 'error'], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function registerState(): DataResponse
    {
        try {
            $params = $this->request->getParams();
            $providerId = filter_var($params['providerId'] ?? null, FILTER_VALIDATE_INT);
            if ($providerId === false || $providerId < 1) {
                throw new UnexpectedValueException('Provider ID is invalid');
            }

            $provider = $this->ioidcProviderMapper->get($providerId);
            $issuer = $provider->getIssuer();
            $jwksUri = $provider->getJwksUri();
            if (!is_string($issuer) || $issuer === '' || !is_string($jwksUri) || $jwksUri === '') {
                throw new UnexpectedValueException('Provider requires administrator reconfiguration');
            }

            $state = $this->randomBase64Url(32);
            $nonce = $this->randomBase64Url(32);
            $id = $this->ioidcStateMapper->register_state([
                'providerId' => $providerId,
                'providerVersion' => max(1, (int)$provider->getConfigVersion()),
                'state' => hash('sha256', $state),
                'nonceHash' => hash('sha256', $nonce),
                'createdAt' => time(),
                'uid' => $this->userId,
            ]);

            return new DataResponse([
                'status' => 'success',
                'id' => $id,
                'state' => $state,
                'nonce' => $nonce,
            ], Http::STATUS_OK);
        } catch (Throwable $e) {
            $this->logger->warning('OIDC transaction creation rejected', ['reason' => $e::class]);

            return new DataResponse(['status' => 'error'], Http::STATUS_BAD_REQUEST);
        }
    }

    public function remove(): DataResponse
    {
        $params = $this->request->getParams();
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return new DataResponse(['status' => 'error'], Http::STATUS_BAD_REQUEST);
        }
        if ($this->ioidcUserMapper->has_provider_connections($id)) {
            return new DataResponse(['status' => 'error', 'reason' => 'provider_in_use'], Http::STATUS_CONFLICT);
        }
        $entity = $this->ioidcProviderMapper->get($id);
        $this->ioidcProviderMapper->delete($entity);

        return new DataResponse(['status' => 'success'], Http::STATUS_OK);
    }

    /**
     * @NoAdminRequired
     */
    public function removeUser(): DataResponse
    {
        $params = $this->request->getParams();
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return new DataResponse(['status' => 'error'], Http::STATUS_BAD_REQUEST);
        }

        $provider = $this->ioidcUserMapper->get_refresh_token($id, $this->userId);
        if ($provider === []) {
            return new DataResponse(['status' => 'error'], Http::STATUS_BAD_REQUEST);
        }

        $force = filter_var($params['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $remoteRevocation = 'not_supported';
        if ((int)$provider['provider_version'] !== (int)$provider['config_version']) {
            $remoteRevocation = 'skipped_provider_changed';
            if (!$force) {
                return new DataResponse([
                    'status' => 'error',
                    'reason' => $remoteRevocation,
                    'canForce' => true,
                ], Http::STATUS_CONFLICT);
            }
            $this->logger->warning('Remote OIDC token revocation skipped because provider configuration changed', [
                'providerId' => (int)$provider['provider_id'],
            ]);
        } elseif (is_string($provider['revoke_endpoint']) && $provider['revoke_endpoint'] !== '') {
            try {
                $this->oidcClient->revokeRefreshToken($provider, (string)$provider['refresh_token']);
                $remoteRevocation = 'completed';
            } catch (Throwable $e) {
                $remoteRevocation = 'failed';
                $this->logger->warning('Remote OIDC token revocation failed', [
                    'providerId' => (int)$provider['provider_id'],
                    'reason' => $e::class,
                ]);
                if (!$force) {
                    return new DataResponse([
                        'status' => 'error',
                        'reason' => 'revocation_failed',
                        'canForce' => true,
                    ], Http::STATUS_BAD_GATEWAY);
                }
            }
        }

        $this->ioidcUserMapper->delete_user($id, $this->userId);
        $this->ioidcStateMapper->delete_userstate([
            'uid' => $this->userId,
            'provider_id' => (int)$provider['provider_id'],
        ]);

        return new DataResponse([
            'status' => 'success',
            'remoteRevocation' => $remoteRevocation,
        ], Http::STATUS_OK);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function prepareProviderParams(array $params, bool $updating): array
    {
        foreach (['client_id', 'name', 'scope'] as $field) {
            $this->requestString($params, $field);
        }
        if (!$updating) {
            $this->requestString($params, 'client_secret');
        }

        return $this->oidcClient->discoverProvider($params);
    }

    /** @param array<string, mixed> $params */
    private function hasSecuritySensitiveChanges(IOIDCProvider $provider, array $params): bool
    {
        $current = [
            'issuer' => $provider->getIssuer(),
            'jwks_uri' => $provider->getJwksUri(),
            'auth_endpoint' => $provider->getAuthEndpoint(),
            'token_endpoint' => $provider->getTokenEndpoint(),
            'revoke_endpoint' => $provider->getRevokeEndpoint() ?? '',
            'client_id' => $provider->getClientId(),
            'scope' => $provider->getScope(),
            'token_endpoint_auth_method' => $provider->getTokenEndpointAuthMethod(),
        ];
        foreach ($current as $field => $value) {
            if (($params[$field] ?? null) !== $value) {
                return true;
            }
        }

        return isset($params['client_secret'])
            && $params['client_secret'] !== ''
            && $params['client_secret'] !== $provider->getClientSecret();
    }

    /** @param array<string, mixed> $params */
    private function requestString(array $params, string $field): string
    {
        $value = $params[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new UnexpectedValueException(sprintf('Request field "%s" is missing', $field));
        }

        return $value;
    }

    private function randomBase64Url(int $bytes): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }
}
