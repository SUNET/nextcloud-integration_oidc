<?php

declare(strict_types=1);

namespace OCA\IOIDC\Tests\Unit\Controller;

use OCA\IOIDC\Controller\ApiController;
use OCA\IOIDC\Db\IOIDCProvider;
use OCA\IOIDC\Db\IOIDCProviderMapper;
use OCA\IOIDC\Db\IOIDCStateMapper;
use OCA\IOIDC\Db\IOIDCUserMapper;
use OCA\IOIDC\Service\OidcClient;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ApiControllerSecurityTest extends TestCase
{
	private IRequest&MockObject $request;
	private IURLGenerator&MockObject $urlGenerator;
	private IOIDCUserMapper&MockObject $userMapper;
	private IOIDCProviderMapper&MockObject $providerMapper;
	private IOIDCStateMapper&MockObject $stateMapper;
	private IUserSession&MockObject $userSession;
	private OidcClient&MockObject $oidcClient;

	protected function setUp(): void
	{
		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userMapper = $this->createMock(IOIDCUserMapper::class);
		$this->providerMapper = $this->createMock(IOIDCProviderMapper::class);
		$this->stateMapper = $this->createMock(IOIDCStateMapper::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->oidcClient = $this->createMock(OidcClient::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);
	}

	public function testProviderQueryNeverReturnsClientSecret(): void
	{
		$provider = new IOIDCProvider();
		$provider->setClientSecret('provider-secret-sentinel');
		$this->providerMapper->method('query')->willReturn([$provider]);

		$data = $this->controller()->query()->getData();
		$serialized = json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

		self::assertArrayNotHasKey('clientSecret', $serialized[0]);
		self::assertStringNotContainsString('provider-secret-sentinel', json_encode($serialized, JSON_THROW_ON_ERROR));
	}

	public function testUserQueryNeverReturnsClientSecret(): void
	{
		$this->userMapper->method('query_user')->with('alice')->willReturn([[
			'id' => 1,
			'provider_id' => 2,
			'name' => 'Provider',
			'client_secret' => 'provider-secret-sentinel',
		]]);

		$data = $this->controller()->queryUser()->getData();

		self::assertArrayNotHasKey('client_secret', $data[0]);
		self::assertStringNotContainsString('provider-secret-sentinel', json_encode($data, JSON_THROW_ON_ERROR));
	}

	public function testStateRegistrationCannotTargetAnotherUser(): void
	{
		$this->request->method('getParams')->willReturn([
			'providerId' => 2,
			'state' => 'attacker-state',
			'uid' => 'bob',
		]);
		$provider = new IOIDCProvider();
		$provider->setIssuer('https://issuer.example.test');
		$provider->setJwksUri('https://issuer.example.test/jwks');
		$provider->setConfigVersion(1);
		$this->providerMapper->method('get')->with(2)->willReturn($provider);
		$this->stateMapper->expects(self::once())
			->method('register_state')
			->with(self::callback(static fn (array $params): bool => $params['uid'] === 'alice'
				&& $params['state'] !== hash('sha256', 'attacker-state')
				&& preg_match('/\A[0-9a-f]{64}\z/', $params['state']) === 1
				&& preg_match('/\A[0-9a-f]{64}\z/', $params['nonceHash']) === 1))
			->willReturn(1);

		$data = $this->controller()->registerState()->getData();
		self::assertSame('success', $data['status']);
		self::assertNotSame('attacker-state', $data['state']);
		self::assertArrayNotHasKey('uid', $data);
	}

	public function testDisconnectLookupIsScopedToCurrentUser(): void
	{
		$this->request->method('getParams')->willReturn(['id' => 42]);
		$this->userMapper->expects(self::once())
			->method('get_refresh_token')
			->with(42, 'alice')
			->willReturn([]);

		$this->controller()->removeUser();
	}

	public function testFailedRemoteRevocationRetainsLocalConnection(): void
	{
		$this->request->method('getParams')->willReturn(['id' => 42]);
		$this->userMapper->method('get_refresh_token')->with(42, 'alice')->willReturn($this->connectionRow());
		$this->oidcClient->method('revokeRefreshToken')->willThrowException(new \RuntimeException('failed'));
		$this->userMapper->expects(self::never())->method('delete_user');
		$this->stateMapper->expects(self::never())->method('delete_userstate');

		$response = $this->controller()->removeUser();

		self::assertSame(Http::STATUS_BAD_GATEWAY, $response->getStatus());
		self::assertTrue($response->getData()['canForce']);
	}

	public function testConfirmedLocalRemovalContinuesAfterRevocationFailure(): void
	{
		$this->request->method('getParams')->willReturn(['id' => 42, 'force' => true]);
		$this->userMapper->method('get_refresh_token')->with(42, 'alice')->willReturn($this->connectionRow());
		$this->oidcClient->method('revokeRefreshToken')->willThrowException(new \RuntimeException('failed'));
		$this->userMapper->expects(self::once())->method('delete_user')->with(42, 'alice');
		$this->stateMapper->expects(self::once())->method('delete_userstate');

		$response = $this->controller()->removeUser();

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame('failed', $response->getData()['remoteRevocation']);
	}

	private function controller(): ApiController
	{
		return new ApiController(
			'integration_oidc',
			$this->request,
			$this->urlGenerator,
			$this->userMapper,
			$this->providerMapper,
			$this->stateMapper,
			$this->userSession,
			new NullLogger(),
			$this->oidcClient,
		);
	}

	/** @return array<string, mixed> */
	private function connectionRow(): array
	{
		return [
			'refresh_token' => 'refresh-token',
			'revoke_endpoint' => 'https://issuer.example.test/revoke',
			'provider_id' => 2,
			'provider_version' => 1,
			'config_version' => 1,
		];
	}
}
