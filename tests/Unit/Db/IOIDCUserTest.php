<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Tests\Unit\Db;

use OCA\IOIDC\Db\IOIDCUser;
use PHPUnit\Framework\TestCase;

final class IOIDCUserTest extends TestCase
{
	public function testSerializationNeverContainsTokens(): void
	{
		$user = new IOIDCUser();
		$user->setAccessToken('access-token-sentinel');
		$user->setRefreshToken('refresh-token-sentinel');

		$data = $user->jsonSerialize();
		$encoded = json_encode($data, JSON_THROW_ON_ERROR);

		self::assertArrayNotHasKey('access_token', $data);
		self::assertArrayNotHasKey('accessToken', $data);
		self::assertArrayNotHasKey('refresh_token', $data);
		self::assertArrayNotHasKey('refreshToken', $data);
		self::assertStringNotContainsString('access-token-sentinel', $encoded);
		self::assertStringNotContainsString('refresh-token-sentinel', $encoded);
	}
}
