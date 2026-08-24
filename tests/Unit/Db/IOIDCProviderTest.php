<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Tests\Unit\Db;

use OCA\IOIDC\Db\IOIDCProvider;
use PHPUnit\Framework\TestCase;

final class IOIDCProviderTest extends TestCase
{
	public function testSerializationNeverContainsClientSecret(): void
	{
		$provider = new IOIDCProvider();
		$provider->setClientSecret('secret-sentinel');

		$data = $provider->jsonSerialize();

		self::assertArrayNotHasKey('clientSecret', $data);
		self::assertArrayNotHasKey('client_secret', $data);
		self::assertFalse(in_array('secret-sentinel', $data, true));
		self::assertTrue($data['hasClientSecret']);
	}

	public function testBlankSecretOnUpdatePreservesStoredSecret(): void
	{
		$provider = new IOIDCProvider();
		$provider->setClientSecret('stored-secret');
		$params = $this->providerParams();
		$params['client_secret'] = '';

		$provider->setParams($params, true);

		self::assertSame('stored-secret', $provider->getClientSecret());
	}

	public function testNonBlankSecretOnUpdateRotatesStoredSecret(): void
	{
		$provider = new IOIDCProvider();
		$provider->setClientSecret('stored-secret');
		$params = $this->providerParams();
		$params['client_secret'] = 'rotated-secret';

		$provider->setParams($params, true);

		self::assertSame('rotated-secret', $provider->getClientSecret());
	}

	/** @return array<string, string> */
	private function providerParams(): array
	{
		return [
			'access_type' => '',
			'auth_endpoint' => 'https://issuer.example.test/authorize',
			'client_id' => 'client',
			'client_secret' => 'new-secret',
			'display' => '',
			'domain_hint' => '',
			'hd' => '',
			'include_granted_scopes' => '',
			'issuer' => 'https://issuer.example.test',
			'jwks_uri' => 'https://issuer.example.test/jwks',
			'login_hint' => '',
			'name' => 'Provider',
			'prompt' => '',
			'response_mode' => '',
			'response_type' => 'code',
			'revoke_endpoint' => 'https://issuer.example.test/revoke',
			'scope' => 'openid offline_access',
			'tenant' => '',
			'token_endpoint' => 'https://issuer.example.test/token',
			'token_endpoint_auth_method' => 'client_secret_basic',
			'user_endpoint' => 'https://issuer.example.test/userinfo',
		];
	}
}
