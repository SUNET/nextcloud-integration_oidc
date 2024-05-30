<?php

namespace OCA\IOIDC\Controller;

use OCA\IOIDC\Db\IOIDCConnection;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\IRequest;

class ApiController extends Controller
{
  private IOIDCConnection $ioidcConnection;
  public function __construct(
    string $appName,
    IRequest $request,
    IOIDCConnection $ioidcConnection
  ) {
    parent::__construct($appName, $request);
    $this->ioidcConnection = $ioidcConnection;
  }
  /**
   * @NoCSRFRequired
   *
   * @return DataResponse
   **/
  public function query()
  {
    $response = $this->ioidcConnection->query();
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
    $id = $this->ioidcConnection->register($params['name'], $params['client_id'], $params['client_secret'], $params['token_endpoint']);
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
}
