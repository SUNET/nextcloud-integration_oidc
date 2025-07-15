<?php

namespace OCA\IOIDC\BackgroundJob;

use OCA\IOIDC\Db\IOIDCUserMapper;
use OCA\IOIDC\Db\IOIDCProviderMapper;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class RefreshTokens extends TimedJob
{
  private IOIDCProviderMapper $ioidcProviderMapper;
  private IOIDCUserMapper $ioidcUserMapper;
  protected int $interval;
  private Client $client;
  private LoggerInterface $logger;
  public function __construct(
    ITimeFactory $time,
    IOIDCProviderMapper $ioidcProviderMapper,
    IOIDCUserMapper $ioidcUserMapper,
    LoggerInterface $logger
  ) {
    parent::__construct($time);
    $this->client = new Client();
    $this->ioidcProviderMapper = $ioidcProviderMapper;
    $this->ioidcUserMapper = $ioidcUserMapper;
    $this->logger = $logger;
    $this->interval = 3600;
    $this->setInterval($this->interval);
  }

  protected function run($arguments)
  {
    foreach ($this->ioidcUserMapper->get_all_accesstoken() as $token) {
      $is_expiring = $token['timestamp'] + $token['expires_in'] < time() + $this->interval;
      try {
        if ($is_expiring) {
          $this->logger->info("Refreshing token for {$token['uid']} (expired at " . gmdate('Y-m-d H:i:s', $token['timestamp'] + $token['expires_in']) . ").");
          $this->refresh_token($token);
        } else {
          $this->logger->debug("Token for {$token['uid']} is still valid");
        }
      } catch (\Throwable $e) {
        $this->logger->error("Failed to handle token for {$token['uid']}: " . $e->getMessage(), [
          'exception' => $e,
        ]);
        continue;
      }
    }
  }

  private function refresh_token(array $token)
  {
    $client_id = $token['client_id'];
    $client_secret = $token['client_secret'];
    $id = $token['id'];
    $refresh_token = $token['refresh_token'];
    $token_endpoint = $token['token_endpoint'];
    $uid = $token['uid'];
    $provider = $this->ioidcProviderMapper->get($token['provider_id']);

    $this->logger->info("Refreshing token for {$uid}");
    $this->logger->debug("Token endpoint: {$token_endpoint}");
    $this->logger->debug("Client ID: {$client_id}");
    $this->logger->debug("Requested scope: " . $provider->getScope());

    try {
      $response = $this->client->post(
        $token_endpoint,
        [
          'form_params' => [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'scope' => $provider->getScope(),
          ],
          'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
        ]
      );
    } catch (\Exception $e) {
      $this->logger->error("Failed to refresh token for {$uid}: " . $e->getMessage());
      throw $e;
    }

    $body = json_decode($response->getBody()->getContents(), true);

    if (!isset($body['access_token'])) {
      $this->logger->error("No access_token in response for {$uid}");
      $this->logger->debug("Response body: " . json_encode($body));
      throw new \RuntimeException("Invalid token response");
    }

    $access_token = $body['access_token'];
    $expires_in = $body['expires_in'];
    $scope = $body['scope'];
    $token_type = $body['token_type'];

    $this->logger->info("Received new token for {$uid}, expires in {$expires_in} seconds");
    $this->logger->debug("Token type: {$token_type}, scope: {$scope}");

    $this->ioidcUserMapper->refresh_token([
      'access_token' => $access_token,
      'expires_in' => $expires_in,
      'id' => $id,
      'scope' => $scope,
      'token_type' => $token_type,
      'uid' => $uid
    ]);
  }
}
