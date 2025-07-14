<?php

namespace OCA\IOIDC\Controller;

use OCA\IOIDC\Db\IOIDCUserMapper;
use OCA\IOIDC\Db\IOIDCProviderMapper;
use OCA\IOIDC\Db\IOIDCStateMapper;
use OCP\IURLGenerator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use OCP\IUserSession;

class ApiController extends Controller
{
    private string $userId;
    private IURLGenerator $urlGenerator;
    private IOIDCUserMapper $ioidcUserMapper;
    private IOIDCProviderMapper $ioidcProviderMapper;
    private IOIDCStateMapper $ioidcStateMapper;
    private Client $client;
    private LoggerInterface $logger;
    public function __construct(
        string $appName,
        IRequest $request,
        IURLGenerator $urlGenerator,
        IOIDCUserMapper $ioidcUserMapper,
        IOIDCProviderMapper $ioidcProviderMapper,
        IOIDCStateMapper $ioidcStateMapper,
        IUserSession $userSession,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->ioidcUserMapper = $ioidcUserMapper;
        $this->ioidcProviderMapper = $ioidcProviderMapper;
        $this->ioidcStateMapper = $ioidcStateMapper;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        $this->client = new Client();
        $this->userId = $userSession->getUser()->getUID();
    }
    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return RedirectResponse
     **/
    public function callback(): RedirectResponse
    {
        $params = $this->request->getParams();
        $code = $params['code'];
        $state = $params['state'];
        $result = $this->ioidcStateMapper->query_state($this->userId, $state);

        $provider_id = $result['provider_id'];
        $client_id = $result['client_id'];
        $client_secret = $result['client_secret'];
        $token_endpoint = $result['token_endpoint'];

        $redirect_uri = $this->urlGenerator->getAbsoluteURL('/index.php/apps/integration_oidc/callback');

        $payload = ['form_params' => [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri,
        ]];
        $response = $this->client->post(
            $token_endpoint,
            $payload
        );

        $provider = $this->ioidcProviderMapper->get($provider_id);
        $this->logger->debug('provider: ' . print_r($provider, true));
        $body = json_decode($response->getBody()->getContents());
        $this->logger->debug('body: ' . print_r($body, true));
        $access_token = $body->access_token;
        $expires_in = $body->expires_in;
        $refresh_token = $body->refresh_token;
        $scope = $body->scope;
        $token_type = $body->token_type;
        $id_token = $body->id_token;

        // --- JWT decoding ---
        $jwt_parts = explode('.', $id_token);
        if (count($jwt_parts) !== 3) {
            throw new \RuntimeException('Malformed ID token');
        }

        $payload = $jwt_parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4); // fix padding
        $payload = strtr($payload, '-_', '+/');

        $id_obj = json_decode(base64_decode($payload));
        if (!$id_obj) {
            throw new \RuntimeException('Failed to decode ID token payload');
        }

        $email = $id_obj->email ?? null;
        $sub = $id_obj->sub ?? null;

        if (!$sub) {
            throw new \RuntimeException('Missing "sub" claim in ID token');
        }

        $this->ioidcUserMapper->register_user([
            'accessToken' => $access_token,
            'email' => $email,
            'expiresIn' => $expires_in,
            'providerId' => $provider_id,
            'refreshToken' => $refresh_token,
            'scope' => $scope,
            'sub' => $sub,
            'tokenType' => $token_type,
            'timestamp' => time(),
            'uid' => $this->userId
        ]);

        $url = $this->urlGenerator->getAbsoluteURL('/index.php/settings/user/connected-accounts');
        return new RedirectResponse($url, Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     **/
    public function query(): DataResponse
    {
        $response = $this->ioidcProviderMapper->query();
        return new DataResponse($response, Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     **/
    public function queryUser(): DataResponse
    {
        $response = $this->ioidcUserMapper->query_user($this->userId);
        return new DataResponse($response, Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     *
     * @return DataResponse
     **/
    public function register(): DataResponse
    {
        $params = $this->request->getParams();
        $id = $this->ioidcProviderMapper->register($params);
        $response = ['status' => "success", "id" => $id];
        return new DataResponse($response, Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     **/
    public function registerState(): DataResponse
    {
        $params = $this->request->getParams();
        $id = $this->ioidcStateMapper->register_state($params);
        $status = ['status' => "success", "id" => $id];
        $response = array_merge($status, $params);
        return new DataResponse($response, Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     *
     * @return DataResponse
     **/
    public function remove(): DataResponse
    {
        $params = $this->request->getParams();
        $entity = $this->ioidcProviderMapper->get($params['id']);
        $this->ioidcProviderMapper->delete($entity);
        return new DataResponse(['status' => "success"], Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     **/
    public function removeUser(): DataResponse
    {
        $params = $this->request->getParams();
        $response = $this->ioidcUserMapper->get_refresh_token($params['id']);
        if (!$response) {
            return new DataResponse(['status' => "error"], Http::STATUS_BAD_REQUEST);
        }
        $this->client->post(
            $response['revoke_endpoint'],
            [
                'form_params' => [
                    'token' => $response['refresh_token'],
                    'grant_type' => 'refresh_token',
                    'code' => $response['code']
                ]
            ]
        );
        $state = array('uid' => $this->userId, 'provider_id' => $response['provider_id']);
        $this->ioidcUserMapper->delete_user($params['id']);
        $this->ioidcStateMapper->delete_userstate($state);
        return new DataResponse(['status' => "success"], Http::STATUS_OK);
    }
}
