<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Tests\Unit\Service;

use OCA\IOIDC\Security\IdTokenValidator;
use OCA\IOIDC\Service\OidcClient;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class OidcClientTest extends TestCase
{
	private IClient&MockObject $httpClient;
	private OidcClient $client;

	protected function setUp(): void
	{
		$this->httpClient = $this->createMock(IClient::class);
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($this->httpClient);
		$this->client = new OidcClient($clientService, new IdTokenValidator());
	}

	public function testDiscoveryPinsIssuerAndSafeEndpoints(): void
	{
		$this->httpClient->expects(self::once())
			->method('get')
			->with(
				'https://issuer.example.test/.well-known/openid-configuration',
				self::callback(static fn (array $options): bool => $options['allow_redirects'] === false
					&& !isset($options['nextcloud']['allow_local_address'])),
			)
			->willReturn($this->jsonResponse($this->metadata()));

		$result = $this->client->discoverProvider($this->providerInput());

		self::assertSame('https://issuer.example.test/authorize', $result['auth_endpoint']);
		self::assertSame('https://issuer.example.test/token', $result['token_endpoint']);
		self::assertSame('https://issuer.example.test/jwks', $result['jwks_uri']);
		self::assertSame('client_secret_basic', $result['token_endpoint_auth_method']);
		self::assertSame('code', $result['response_type']);
	}

	public function testDiscoveryRejectsHttpIssuerWithoutSendingRequest(): void
	{
		$this->httpClient->expects(self::never())->method('get');
		$params = $this->providerInput();
		$params['issuer'] = 'http://issuer.example.test';

		$this->expectException(UnexpectedValueException::class);
		$this->client->discoverProvider($params);
	}

	public function testDiscoveryRejectsIssuerMismatch(): void
	{
		$metadata = $this->metadata();
		$metadata['issuer'] = 'https://attacker.example.test';
		$this->httpClient->method('get')->willReturn($this->jsonResponse($metadata));

		$this->expectException(UnexpectedValueException::class);
		$this->client->discoverProvider($this->providerInput());
	}

	public function testCodeExchangeUsesBasicAuthenticationAndNoRedirects(): void
	{
		$this->httpClient->expects(self::once())
			->method('post')
			->with(
				'https://issuer.example.test/token',
				self::callback(static function (array $options): bool {
					return $options['allow_redirects'] === false
						&& $options['auth'] === ['client', 'secret-sentinel']
						&& !array_key_exists('client_secret', $options['form_params'])
						&& $options['form_params']['code'] === 'authorization-code'
						&& $options['form_params']['scope'] === 'openid offline_access';
				}),
			)
			->willReturn($this->jsonResponse([
				'access_token' => 'access-token',
				'expires_in' => 3600,
				'refresh_token' => 'refresh-token',
				'token_type' => 'Bearer',
				'id_token' => 'header.payload.signature',
			]));

		$result = $this->client->exchangeAuthorizationCode(
			$this->providerRecord(),
			'authorization-code',
			'https://cloud.example.test/apps/integration_oidc/callback',
		);

		self::assertSame('access-token', $result['access_token']);
		self::assertSame('refresh-token', $result['refresh_token']);
	}

	/** @param array<string, mixed> $data */
	private function jsonResponse(array $data): IResponse
	{
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn(200);
		$response->method('getBody')->willReturn(json_encode($data, JSON_THROW_ON_ERROR));

		return $response;
	}

	/** @return array<string, mixed> */
	private function metadata(): array
	{
		return [
			'issuer' => 'https://issuer.example.test',
			'authorization_endpoint' => 'https://issuer.example.test/authorize',
			'token_endpoint' => 'https://issuer.example.test/token',
			'jwks_uri' => 'https://issuer.example.test/jwks',
			'userinfo_endpoint' => 'https://issuer.example.test/userinfo',
			'revocation_endpoint' => 'https://issuer.example.test/revoke',
			'response_types_supported' => ['code'],
			'id_token_signing_alg_values_supported' => ['RS256'],
			'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
		];
	}

	/** @return array<string, mixed> */
	private function providerInput(): array
	{
		return [
			'issuer' => 'https://issuer.example.test',
			'scope' => 'openid offline_access',
			'revoke_endpoint' => 'https://issuer.example.test/revoke',
			'user_endpoint' => 'https://issuer.example.test/userinfo',
		];
	}

	/** @return array<string, mixed> */
	private function providerRecord(): array
	{
		return [
			'client_id' => 'client',
			'client_secret' => 'secret-sentinel',
			'scope' => 'openid offline_access',
			'token_endpoint' => 'https://issuer.example.test/token',
			'token_endpoint_auth_method' => 'client_secret_basic',
		];
	}
}
