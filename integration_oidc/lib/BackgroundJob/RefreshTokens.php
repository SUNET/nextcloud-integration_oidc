<?php

namespace OCA\IOIDC\BackgroundJob;

use OCA\IOIDC\Db\IOIDCConnection;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use GuzzleHttp\Client;

class RefreshTokens extends TimedJob
{

  private IOIDCConnection $ioidcConnection;
  private int $interval;
  private Client $client;
  public function __construct(
    ITimeFactory $time,
    IOIDCConnection $ioidcConnection
  ) {
    parent::__construct($time);
    $this->client = new Client();
    $this->ioidcConnection = $ioidcConnection;
    $this->interval = 3600;
    $this->setInterval($this->interval);
  }

  protected function run($arguments)
  {
    foreach ($this->ioidcConnection->get_all_accesstoken() as $token) {
      $is_expiring = $token['timestamp'] + $token['expires_in'] > time() + $this->interval;
      if ($is_expiring) {
        $this->refresh_token($token);
      }
    }
  }

  private function refresh_token($token)
  {
    $client_id = $token['client_id'];
    $client_secret = $token['client_secret'];
    $id = $token['id'];
    $refresh_token = $token['refresh_token'];
    $token_endpoint = $token['token_endpoint'];
    $uid = $token['uid'];
    $response = $this->client->post(
      $token_endpoint,
      [
        'form_params' => [
          'client_id' => $client_id,
          'client_secret' => $client_secret,
          'grant_type' => 'refresh_token',
          'refresh_token' => $refresh_token,
        ]
      ]
    );
    $body = json_decode($response->getBody()->getContents());
    $access_token = $body['access_token'];
    $expires_in = $body['expires_in'];
    $scope = $body['scope'];
    $token_type = $body['token_type'];
    $this->ioidcConnection->refresh_token([
      'access_token' => $access_token,
      'expires_in' => $expires_in,
      'id' => $id,
      'scope' => $scope,
      'token_type' => $token_type,
      'uid' => $uid
    ]);
  }
}
