<?php

namespace OCA\IOIDC\Controller;

use OCA\IOIDC\Db\IOIDCConnection;
use \OCP\IURLGenerator;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\IRequest;
use GuzzleHttp\Client;

class ApiController extends Controller
{
  private $userId;
  private IOIDCConnection $ioidcConnection;
  private IURLGenerator $urlGenerator;
  public function __construct(
    string $userId,
    string $appName,
    IRequest $request,
    IURLGenerator $urlGenerator,
    IOIDCConnection $ioidcConnection
  ) {
    parent::__construct($appName, $request);
    $this->userId = $userId;
    $this->ioidcConnection = $ioidcConnection;
    $this->urlGenerator = $urlGenerator;
  }
  /**
   * @NoCSRFRequired
   * @NoAdminRequired
   * @return DataResponse
   **/
  public function callback()
  {
    $params = $this->request->getParams();
    $code = $params['code'];
    $state = $params['state'];
    $result = $this->ioidcConnection->query_state($this->userId, $state);

    $provider_id = $result['provider_id'];
    $client_id = $result['client_id'];
    $client_secret = $result['client_secret'];
    $grant_type = $result['grant_type'];
    $token_endpoint = $result['token_endpoint'];

    $client = new Client();
    $redirect_uri = $this->urlGenerator->getAbsoluteURL('/apps/integration_oidc/callback');

    $response = $client->post(
      $token_endpoint,
      [
        'form_params' => [
          'client_id' => $client_id,
          'client_secret' => $client_secret,
          'grant_type' => $grant_type,
          'code' => $code,
          'redirect_uri' => $redirect_uri,
        ]
      ]
    );

    $body = json_decode($response->getBody()->getContents());
    $access_token = $body->access_token;
    $refresh_token = $body->refresh_token;
    $expires_in = $body->expires_in;
    $token_type = $body->token_type;
    $scope = $body->scope;
    $this->ioidcConnection->register_user([
      'uid' => $this->userId,
      'provider_id' => $provider_id,
      'access_token' => $access_token,
      'refresh_token' => $refresh_token,
      'expires_in' => $expires_in,
      'token_type' => $token_type,
      'scope' => $scope
    ]);

    $url = $this->urlGenerator->getAbsoluteURL('/settings/user/connected-accounts');
    return new RedirectResponse($url, Http::STATUS_OK);
  }
  /**
   * @NoCSRFRequired
   * @NoAdminRequired
   * @return DataResponse
   **/
  public function query()
  {
    $response = $this->ioidcConnection->query();
    return new DataResponse($response, Http::STATUS_OK);
  }
  /**
   * @NoCSRFRequired
   * @NoAdminRequired
   * @return DataResponse
   **/
  public function queryUser()
  {
    $response = $this->ioidcConnection->query_user($this->userId);
    return new DataResponse($response, Http::STATUS_OK);
  }
  /**
   * @NoCSRFRequired
   *
   * @return DataResponse
   **/
  public function register()
  {
    $params = $this->request->getParams();
    $id = $this->ioidcConnection->register($params);
    return new DataResponse(['status' => "success", "id" => $id], Http::STATUS_OK);
  }
  /**
   * @NoCSRFRequired
   * @NoAdminRequired
   * @return DataResponse
   **/
  public function registerState()
  {
    $params = $this->request->getParams();
    $params['uid'] = $this->userId;
    $id = $this->ioidcConnection->register_state($params);
    return new DataResponse(['status' => "success", "id" => $id], Http::STATUS_OK);
  }
  /**
   * @NoCSRFRequired
   *
   * @return DataResponse
   **/
  public function remove()
  {
    $params = $this->request->getParams();
    $this->ioidcConnection->remove($params['id']);
    return new DataResponse(['status' => "success"], Http::STATUS_OK);
  }
  /**
   * @NoCSRFRequired
   * @NoAdminRequired
   * @return DataResponse
   **/
  public function removeUser()
  {
    $params = $this->request->getParams();
    $params['uid'] = $this->userId;
    $this->ioidcConnection->remove_user($params);
    return new DataResponse(['status' => "success"], Http::STATUS_OK);
  }
}
