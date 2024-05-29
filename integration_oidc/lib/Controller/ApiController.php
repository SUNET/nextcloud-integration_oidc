<?php

namespace OCA\IOIDC\Controller;

use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\IRequest;

class ApiController extends Controller
{
  public function __construct(
    string $appName,
    IRequest $request
  ) {
    parent::__construct($appName, $request);
  }
  /**
   * @NoCSRFRequired
   *
   * @return DataResponse
   **/
  public function query()
  {
    $response = array(
      array("name" => "Test", "client_id" => "tEsT", "client_secret" => "TeSt", "token_endpoint" => "https://accounts.google.com/o/oauth2/v2/auth")
    );
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
    return new DataResponse(['status' => "success"], Http::STATUS_OK);
  }
  /**
   * @NoCSRFRequired
   *
   * @return DataResponse
   **/
  public function remove()
  {
    $params = $this->request->getParams();
    return new DataResponse(['status' => "success"], Http::STATUS_OK);
  }
}
