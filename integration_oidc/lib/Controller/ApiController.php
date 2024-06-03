<?php

namespace OCA\IOIDC\Controller;

use OCA\IOIDC\Db\IOIDCConnection;
use \OCP\IURLGenerator;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\IRequest;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

class ApiController extends Controller
{
  private $userId;
  private IOIDCConnection $ioidcConnection;
  private LoggerInterface $logger;
  private IURLGenerator $urlGenerator;
  public function __construct(
    string $userId,
    string $appName,
    IRequest $request,
    LoggerInterface $logger,
    IURLGenerator $urlGenerator,
    IOIDCConnection $ioidcConnection
  ) {
    parent::__construct($appName, $request);
    $this->userId = $userId;
    $this->ioidcConnection = $ioidcConnection;
    $this->logger = $logger;
    $this->urlGenerator = $urlGenerator;
  }
  /**
   * @NoCSRFRequired
   * @NoAdminRequired
   * @return DataResponse
   **/
  public function callback()
  {
    $this->logger->debug('In IOIDCController::callback', ['app' => 'integration_oidc']);
    $params = $this->request->getParams();
    $code = $params['code'];
    $state = $params['state'];
    $result = $this->ioidcConnection->query_state($this->userId, $state);

    $client_id = $result['client_id'];
    $client_secret = $result['client_secret'];
    $grant_type = $result['grant_type'];
    $client = new Client();
    $url = $this->urlGenerator->getAbsoluteURL('/settings/personal/connected-accounts');

    $response = $client->request(
      'POST',
      $result['token_endpoint'],
      [
        'form_params' => [
          'client_id' => $client_id,
          'client_secret' => $client_secret,
          'grant_type' => $grant_type,
          'code' => $code,
          'redirect_uri' => $url
        ]
      ]
    );

    // Parse the response object, e.g. read the headers, body, etc.
    // $headers = $response->getHeaders();
    // $body = $response->getBody();

    return new DataResponse('', Http::STATUS_OK);
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
   * @NoAdminRequired
   * @return DataResponse
   **/
  public function registerUser()
  {
    $params = $this->request->getParams();
    $params['uid'] = $this->userId;
    $id = $this->ioidcConnection->register_user($params);
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
