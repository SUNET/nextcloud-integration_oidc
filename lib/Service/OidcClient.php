<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Service;

use JsonException;
use OCA\IOIDC\Security\IdTokenValidator;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use RuntimeException;
use UnexpectedValueException;

class OidcClient
{
    private const MAX_RESPONSE_SIZE = 1_048_576;
    private const REQUEST_OPTIONS = [
        'allow_redirects' => false,
        'connect_timeout' => 5,
        'stream' => true,
        'timeout' => 15,
        'headers' => [
            'Accept' => 'application/json',
        ],
    ];

    private IClient $client;

    public function __construct(
        IClientService $clientService,
        private IdTokenValidator $idTokenValidator,
    ) {
        $this->client = $clientService->newClient();
    }

    /**
     * Resolve and validate trusted metadata for provider persistence.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function discoverProvider(array $params): array
    {
        $issuer = $this->requireString($params, 'issuer');
        $this->validateHttpsUrl($issuer, false);

        $metadata = $this->getJson(rtrim($issuer, '/') . '/.well-known/openid-configuration');
        if (($metadata['issuer'] ?? null) !== $issuer) {
            throw new UnexpectedValueException('Discovery issuer does not match the configured issuer');
        }

        $authorizationEndpoint = $this->requireHttpsMetadataUrl($metadata, 'authorization_endpoint');
        $tokenEndpoint = $this->requireHttpsMetadataUrl($metadata, 'token_endpoint');
        $jwksUri = $this->requireHttpsMetadataUrl($metadata, 'jwks_uri');

        $responseTypes = $metadata['response_types_supported'] ?? null;
        if (!is_array($responseTypes) || !in_array('code', $responseTypes, true)) {
            throw new UnexpectedValueException('Provider does not advertise authorization code flow');
        }

        $algorithms = $metadata['id_token_signing_alg_values_supported'] ?? null;
        if (!is_array($algorithms) || !in_array('RS256', $algorithms, true)) {
            throw new UnexpectedValueException('Provider does not advertise RS256 ID tokens');
        }

        $authenticationMethods = $metadata['token_endpoint_auth_methods_supported'] ?? ['client_secret_basic'];
        if (!is_array($authenticationMethods)) {
            throw new UnexpectedValueException('Provider token authentication metadata is invalid');
        }
        if (in_array('client_secret_basic', $authenticationMethods, true)) {
            $authenticationMethod = 'client_secret_basic';
        } elseif (in_array('client_secret_post', $authenticationMethods, true)) {
            $authenticationMethod = 'client_secret_post';
        } else {
            throw new UnexpectedValueException('Provider does not support client secret authentication');
        }

        $scope = $this->requireString($params, 'scope');
        $scopes = preg_split('/\s+/', trim($scope)) ?: [];
        if (!in_array('openid', $scopes, true)) {
            throw new UnexpectedValueException('Provider scope must include openid');
        }

        $revokeEndpoint = $metadata['revocation_endpoint'] ?? ($params['revoke_endpoint'] ?? '');
        $userEndpoint = $metadata['userinfo_endpoint'] ?? ($params['user_endpoint'] ?? '');
        if (!is_string($revokeEndpoint) || !is_string($userEndpoint)) {
            throw new UnexpectedValueException('Provider optional endpoint metadata is invalid');
        }
        if ($revokeEndpoint !== '') {
            $this->validateHttpsUrl($revokeEndpoint);
        }
        if ($userEndpoint !== '') {
            $this->validateHttpsUrl($userEndpoint);
        }

        $params['issuer'] = $issuer;
        $params['auth_endpoint'] = $authorizationEndpoint;
        $params['token_endpoint'] = $tokenEndpoint;
        $params['jwks_uri'] = $jwksUri;
        $params['revoke_endpoint'] = $revokeEndpoint;
        $params['user_endpoint'] = $userEndpoint;
        $params['token_endpoint_auth_method'] = $authenticationMethod;
        $params['response_type'] = 'code';

        return $params;
    }

    /**
     * @param array<string, mixed> $provider
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(array $provider, string $code, string $redirectUri): array
    {
        if ($code === '' || strlen($code) > 4096) {
            throw new UnexpectedValueException('Authorization code is invalid');
        }

        $form = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'scope' => $this->providerString($provider, 'scope'),
        ];
        $options = $this->authenticatedRequestOptions($provider, $form);
        $body = $this->postJson($this->providerUrl($provider, 'token_endpoint'), $options);

        return $this->normalizeTokenResponse($body, true);
    }

    /**
     * @param array<string, mixed> $provider
     * @return array<string, mixed>
     */
    public function refreshToken(array $provider, string $refreshToken, string $scope): array
    {
        if ($refreshToken === '') {
            throw new UnexpectedValueException('Refresh token is missing');
        }

        $form = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => $scope,
        ];
        $options = $this->authenticatedRequestOptions($provider, $form);
        $body = $this->postJson($this->providerUrl($provider, 'token_endpoint'), $options);

        return $this->normalizeTokenResponse($body, false);
    }

    /**
     * @param array<string, mixed> $provider
     */
    public function revokeRefreshToken(array $provider, string $refreshToken): void
    {
        $endpoint = $provider['revoke_endpoint'] ?? null;
        if ($refreshToken === '' || !is_string($endpoint) || $endpoint === '') {
            return;
        }

        $form = [
            'token' => $refreshToken,
            'token_type_hint' => 'refresh_token',
        ];
        $options = $this->authenticatedRequestOptions($provider, $form);
        $response = $this->client->post($this->providerUrl($provider, 'revoke_endpoint'), $options);
        $this->requireSuccess($response);
    }

    /**
     * @param array<string, mixed> $provider
     */
    public function validateIdToken(string $idToken, array $provider, string $nonceHash): object
    {
        $jwks = $this->getJson($this->providerUrl($provider, 'jwks_uri'));

        return $this->idTokenValidator->validate(
            $idToken,
            $jwks,
            $this->providerString($provider, 'issuer'),
            $this->providerString($provider, 'client_id'),
            $nonceHash,
        );
    }

    /**
     * @param array<string, mixed> $provider
     * @param array<string, string> $form
     * @return array<string, mixed>
     */
    private function authenticatedRequestOptions(array $provider, array $form): array
    {
        $clientId = $this->providerString($provider, 'client_id');
        $clientSecret = $this->providerString($provider, 'client_secret');
        $method = $this->providerString($provider, 'token_endpoint_auth_method');

        $options = self::REQUEST_OPTIONS;
        if ($method === 'client_secret_basic') {
            $options['auth'] = [$clientId, $clientSecret];
        } elseif ($method === 'client_secret_post') {
            $form['client_id'] = $clientId;
            $form['client_secret'] = $clientSecret;
        } else {
            throw new UnexpectedValueException('Unsupported token endpoint authentication method');
        }
        $options['form_params'] = $form;

        return $options;
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function normalizeTokenResponse(array $body, bool $requireIdToken): array
    {
        $accessToken = $this->requireString($body, 'access_token');
        $tokenType = $this->requireString($body, 'token_type');
        $expiresIn = $body['expires_in'] ?? null;
        if (!is_int($expiresIn) && !(is_string($expiresIn) && ctype_digit($expiresIn))) {
            throw new UnexpectedValueException('Token response expires_in is invalid');
        }
        $expiresIn = (int)$expiresIn;
        if ($expiresIn < 1 || $expiresIn > 315_360_000) {
            throw new UnexpectedValueException('Token response expires_in is out of range');
        }

        $refreshToken = $body['refresh_token'] ?? null;
        if ($refreshToken !== null && (!is_string($refreshToken) || $refreshToken === '')) {
            throw new UnexpectedValueException('Token response refresh_token is invalid');
        }
        $idToken = $body['id_token'] ?? null;
        if ($requireIdToken && (!is_string($idToken) || $idToken === '')) {
            throw new UnexpectedValueException('Token response ID token is missing');
        }

        return [
            'access_token' => $accessToken,
            'expires_in' => $expiresIn,
            'refresh_token' => $refreshToken,
            'scope' => is_string($body['scope'] ?? null) ? $body['scope'] : '',
            'token_type' => $tokenType,
            'id_token' => $idToken,
        ];
    }

    /** @return array<string, mixed> */
    private function getJson(string $url): array
    {
        $this->validateHttpsUrl($url);
        $response = $this->client->get($url, self::REQUEST_OPTIONS);

        return $this->decodeJsonResponse($response);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function postJson(string $url, array $options): array
    {
        $this->validateHttpsUrl($url);
        $response = $this->client->post($url, $options);

        return $this->decodeJsonResponse($response);
    }

    /** @return array<string, mixed> */
    private function decodeJsonResponse(IResponse $response): array
    {
        $this->requireSuccess($response);
        $body = $response->getBody();
        if (is_resource($body)) {
            $contents = stream_get_contents($body, self::MAX_RESPONSE_SIZE + 1);
            if ($contents === false) {
                throw new RuntimeException('Unable to read OIDC response');
            }
        } elseif (is_string($body)) {
            $contents = $body;
        } else {
            throw new UnexpectedValueException('OIDC response body is missing');
        }
        if (strlen($contents) > self::MAX_RESPONSE_SIZE) {
            throw new UnexpectedValueException('OIDC response body is oversized');
        }

        try {
            $data = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnexpectedValueException('OIDC response is not valid JSON', 0, $e);
        }
        if (!is_array($data) || array_is_list($data)) {
            throw new UnexpectedValueException('OIDC response must be a JSON object');
        }

        return $data;
    }

    private function requireSuccess(IResponse $response): void
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('OIDC endpoint returned HTTP ' . $status);
        }
    }

    /** @param array<string, mixed> $metadata */
    private function requireHttpsMetadataUrl(array $metadata, string $field): string
    {
        $url = $this->requireString($metadata, $field);
        $this->validateHttpsUrl($url);

        return $url;
    }

    private function validateHttpsUrl(string $url, bool $allowQuery = true): void
    {
        $parts = parse_url($url);
        if (
            $parts === false
            || ($parts['scheme'] ?? null) !== 'https'
            || !is_string($parts['host'] ?? null)
            || $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
            || (!$allowQuery && isset($parts['query']))
        ) {
            throw new UnexpectedValueException('OIDC URL must be an HTTPS URL without credentials or fragments');
        }
    }

    /** @param array<string, mixed> $values */
    private function requireString(array $values, string $field): string
    {
        $value = $values[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new UnexpectedValueException(sprintf('Required OIDC field "%s" is missing', $field));
        }

        return trim($value);
    }

    /** @param array<string, mixed> $provider */
    private function providerString(array $provider, string $field): string
    {
        return $this->requireString($provider, $field);
    }

    /** @param array<string, mixed> $provider */
    private function providerUrl(array $provider, string $field): string
    {
        $url = $this->providerString($provider, $field);
        $this->validateHttpsUrl($url);

        return $url;
    }
}
