<?php

namespace OCA\IOIDC\Controller;

use OCA\IOIDC\Db\IOIDCConnection;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\IRequest;

class ApiController extends Controller
{
  private $userId;
  private IOIDCConnection $ioidcConnection;
  public function __construct(
    string $userId,
    string $appName,
    IRequest $request,
    IOIDCConnection $ioidcConnection
  ) {
    parent::__construct($appName, $request);
    $this->userId = $userId;
    $this->ioidcConnection = $ioidcConnection;
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
