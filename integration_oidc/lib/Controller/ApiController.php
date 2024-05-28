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
   * @NoAdminRequired
   * @NoCSRFRequired
   *
   * @return DataResponse
   **/
  public function register()
  {
    return new DataResponse('', Http::STATUS_OK);
  }
  /**
   * @NoAdminRequired
   * @NoCSRFRequired
   *
   * @return DataResponse
   **/
  public function remove()
  {
    return new DataResponse('', Http::STATUS_OK);
  }
}
