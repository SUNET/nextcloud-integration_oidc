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

class ApiController extends Controller
{
    private $userId;
    private IURLGenerator $urlGenerator;
    private IOIDCUserMapper $ioidcUserMapper;
    private IOIDCProviderMapper $ioidcProviderMapper;
    private IOIDCStateMapper $ioidcStateMapper;
    private Client $client;
    private LoggerInterface $logger;
    public function __construct(
        string $userId,
        string $appName,
        IRequest $request,
        IURLGenerator $urlGenerator,
        IOIDCUserMapper $ioidcUserMapper,
        IOIDCProviderMapper $ioidcProviderMapper,
        IOIDCStateMapper $ioidcStateMapper,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->ioidcUserMapper = $ioidcUserMapper;
        $this->ioidcProviderMapper = $ioidcProviderMapper;
        $this->ioidcStateMapper = $ioidcStateMapper;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        $this->client = new Client();
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

        $response = $this->client->post(
            $token_endpoint,
            [
            'form_params' => [
              'client_id' => $client_id,
              'client_secret' => $client_secret,
              'grant_type' => 'authorization_code',
              'code' => $code,
              'redirect_uri' => $redirect_uri,
            ]
      ]
        );

        $body = json_decode($response->getBody()->getContents());
        $this->logger->debug('body: ' . print_r($body, true));
        $access_token = $body->access_token;
        $expires_in = $body->expires_in;
        $refresh_token = $body->refresh_token;
        $prompt = $body->prompt;
        $scope = $body->scope;
        $tenant = $body->tenant;
        $token_type = $body->token_type;
        $id_token = $body->id_token;
        $id_obj = json_decode(base64_decode($id_token));
        $email = $id_obj->email;
        $sub = $id_obj->sub;

        $this->ioidcUserMapper->register_user([
          'access_token' => $access_token,
          'email' => $email,
          'expires_in' => $expires_in,
          'provider_id' => $provider_id,
          'refresh_token' => $refresh_token,
          'prompt' => $prompt,
          'scope' => $scope,
          'tenant' => $tenant,
          'sub' => $sub,
          'token_type' => $token_type,
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
        return new DataResponse(['status' => "success", "id" => $id], Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     * @return DataResponse
     **/
    public function registerState(): DataResponse
    {
        $params = $this->request->getParams();
        $params['uid'] = $this->userId;
        $id = $this->ioidcStateMapper->register_state($params);
        return new DataResponse(['status' => "success", "id" => $id], Http::STATUS_OK);
    }
    /**
     * @NoCSRFRequired
     *
     * @return DataResponse
     **/
    public function remove(): DataResponse
    {
        $params = $this->request->getParams();
        $entity = $this->ioidcStateMapper->get($params['id']);
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
        $params['uid'] = $this->userId;
        $response = $this->ioidcUserMapper->get_refresh_token($params);
        $this->client->post(
            $response['revoke_endpoint'],
            [
            'form_params' => [
              'token' => $response['refresh_token'],
            ]
      ]
        );
        $this->ioidcUserMapper->delete_user($params);
        return new DataResponse(['status' => "success"], Http::STATUS_OK);
    }
}
