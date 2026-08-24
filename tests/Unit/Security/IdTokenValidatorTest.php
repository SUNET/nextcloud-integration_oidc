<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Tests\Unit\Security;

use Firebase\JWT\JWT;
use OCA\IOIDC\Security\IdTokenValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

final class IdTokenValidatorTest extends TestCase
{
	private const CLIENT_ID = 'oidc-client';
	private const ISSUER = 'https://issuer.example.test';
	private const KID = 'test-key';
	private const NONCE = 'one-time-nonce';
	private const NOW = 1_800_000_000;

	private static string $privateKey = '';

	/** @var array{keys: list<array<string, mixed>>} */
	private static array $jwks;

	public static function setUpBeforeClass(): void
	{
		$key = openssl_pkey_new([
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		]);
		if ($key === false || !openssl_pkey_export($key, self::$privateKey)) {
			throw new RuntimeException('Unable to generate test RSA key');
		}

		$details = openssl_pkey_get_details($key);
		if ($details === false || !isset($details['rsa']['n'], $details['rsa']['e'])) {
			throw new RuntimeException('Unable to read test RSA key');
		}

		self::$jwks = ['keys' => [[
			'kty' => 'RSA',
			'use' => 'sig',
			'key_ops' => ['verify'],
			'alg' => 'RS256',
			'kid' => self::KID,
			'n' => self::base64UrlEncode($details['rsa']['n']),
			'e' => self::base64UrlEncode($details['rsa']['e']),
		]]];
	}

	public function testValidTokenIsReturnedAndFirebaseStateIsRestored(): void
	{
		$previousTimestamp = JWT::$timestamp;
		$previousLeeway = JWT::$leeway;
		JWT::$timestamp = 123;
		JWT::$leeway = 7;

		try {
			$claims = $this->validator()->validate(
				$this->token(),
				self::$jwks,
				self::ISSUER,
				self::CLIENT_ID,
				$this->nonceHash(),
				self::NOW,
			);

			self::assertSame('subject-1', $claims->sub);
			self::assertSame(123, JWT::$timestamp);
			self::assertSame(7, JWT::$leeway);
		} finally {
			JWT::$timestamp = $previousTimestamp;
			JWT::$leeway = $previousLeeway;
		}
	}

	public function testRejectsTamperedSignature(): void
	{
		$segments = explode('.', $this->token());
		$segments[2][0] = $segments[2][0] === 'A' ? 'B' : 'A';

		$this->assertRejected(implode('.', $segments));
	}

	public function testRejectsUnsupportedAlgorithm(): void
	{
		$token = JWT::encode($this->validClaims(), str_repeat('secret', 8), 'HS256', self::KID);

		$this->assertRejected($token);
	}

	#[DataProvider('wrongPartyClaimProvider')]
	public function testRejectsWrongIssuerAudienceOrAuthorizedParty(array $override): void
	{
		$this->assertRejected($this->token($override));
	}

	public static function wrongPartyClaimProvider(): array
	{
		return [
			'issuer' => [['iss' => 'https://attacker.example.test']],
			'audience' => [['aud' => 'different-client']],
			'authorized party' => [['azp' => 'different-client']],
		];
	}

	public function testRejectsExpiredTokenBeyondClockSkew(): void
	{
		$this->assertRejected($this->token(['exp' => self::NOW - 61]));
	}

	public function testRejectsFutureIssuedAtBeyondClockSkew(): void
	{
		$this->assertRejected($this->token(['iat' => self::NOW + 61]));
	}

	public function testAllowsClaimsWithinClockSkew(): void
	{
		$claims = $this->validator()->validate(
			$this->token(['iat' => self::NOW + 60, 'exp' => self::NOW - 59]),
			self::$jwks,
			self::ISSUER,
			self::CLIENT_ID,
			$this->nonceHash(),
			self::NOW,
		);

		self::assertSame(self::NOW + 60, $claims->iat);
	}

	public function testRejectsNonceMismatch(): void
	{
		$this->expectException(UnexpectedValueException::class);
		$this->validator()->validate(
			$this->token(),
			self::$jwks,
			self::ISSUER,
			self::CLIENT_ID,
			hash('sha256', 'different-nonce'),
			self::NOW,
		);
	}

	#[DataProvider('requiredClaimProvider')]
	public function testRejectsMissingRequiredClaim(string $claim): void
	{
		$claims = $this->validClaims();
		unset($claims[$claim]);

		$this->assertRejected($this->sign($claims));
	}

	public static function requiredClaimProvider(): array
	{
		return [
			'issuer' => ['iss'],
			'subject' => ['sub'],
			'audience' => ['aud'],
			'expiration' => ['exp'],
			'issued at' => ['iat'],
			'nonce' => ['nonce'],
		];
	}

	public function testAllowsMissingAuthorizedPartyForSingleAudience(): void
	{
		$claims = $this->validClaims();
		unset($claims['azp']);

		$result = $this->validator()->validate(
			$this->sign($claims),
			self::$jwks,
			self::ISSUER,
			self::CLIENT_ID,
			$this->nonceHash(),
			self::NOW,
		);

		self::assertSame('subject-1', $result->sub);
	}

	public function testRequiresAuthorizedPartyForMultipleAudiences(): void
	{
		$claims = $this->validClaims();
		$claims['aud'] = [self::CLIENT_ID, 'another-client'];
		unset($claims['azp']);

		$this->assertRejected($this->sign($claims));
	}

	public function testRejectsDuplicateKeyId(): void
	{
		$jwks = self::$jwks;
		$jwks['keys'][] = self::$jwks['keys'][0];

		$this->assertRejected($this->token(), $jwks);
	}

	public function testAllowsUnrelatedNonSigningKey(): void
	{
		$jwks = self::$jwks;
		$encryptionKey = self::$jwks['keys'][0];
		$encryptionKey['kid'] = 'encryption-key';
		$encryptionKey['use'] = 'enc';
		$encryptionKey['key_ops'] = ['encrypt'];
		$encryptionKey['alg'] = 'RSA-OAEP';
		$jwks['keys'][] = $encryptionKey;

		$claims = $this->validator()->validate(
			$this->token(),
			$jwks,
			self::ISSUER,
			self::CLIENT_ID,
			$this->nonceHash(),
			self::NOW,
		);

		self::assertSame('subject-1', $claims->sub);
	}

	public function testRejectsUnknownAndEmptyKeyIds(): void
	{
		$this->assertRejected($this->sign($this->validClaims(), 'unknown-key'));
		$this->assertRejected($this->sign($this->validClaims(), ''));
	}

	#[DataProvider('incompatibleJwkProvider')]
	public function testRejectsPrivateOrIncompatibleJwk(array $override): void
	{
		$jwks = self::$jwks;
		$jwks['keys'][0] = array_replace($jwks['keys'][0], $override);

		$this->assertRejected($this->token(), $jwks);
	}

	public static function incompatibleJwkProvider(): array
	{
		return [
			'private material' => [['d' => 'AQAB']],
			'key type' => [['kty' => 'oct']],
			'use' => [['use' => 'enc']],
			'key operations' => [['key_ops' => ['sign']]],
			'algorithm' => [['alg' => 'RS512']],
		];
	}

	public function testRejectsMalformedTokenAndJwks(): void
	{
		$this->assertRejected('not-a-jwt');
		$this->assertRejected($this->token(), ['keys' => 'not-a-list']);

		$jwks = self::$jwks;
		$jwks['keys'][0]['n'] = 'not+base64';
		$this->assertRejected($this->token(), $jwks);
	}

	public function testRejectsOversizedTokenAndJwks(): void
	{
		$this->assertRejected(str_repeat('a', 16_385));

		$jwks = self::$jwks;
		$jwks['padding'] = str_repeat('a', 1_048_576);
		$this->assertRejected($this->token(), $jwks);
	}

	/**
	 * @param array<string, mixed> $override
	 */
	private function token(array $override = []): string
	{
		return $this->sign(array_replace($this->validClaims(), $override));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function validClaims(): array
	{
		return [
			'iss' => self::ISSUER,
			'sub' => 'subject-1',
			'aud' => self::CLIENT_ID,
			'azp' => self::CLIENT_ID,
			'exp' => self::NOW + 300,
			'iat' => self::NOW - 10,
			'nonce' => self::NONCE,
		];
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	private function sign(array $claims, string $kid = self::KID): string
	{
		return JWT::encode($claims, self::$privateKey, 'RS256', $kid);
	}

	/**
	 * @param array<string, mixed> $jwks
	 */
	private function assertRejected(string $token, array $jwks = []): void
	{
		try {
			$this->validator()->validate(
				$token,
				$jwks === [] ? self::$jwks : $jwks,
				self::ISSUER,
				self::CLIENT_ID,
				$this->nonceHash(),
				self::NOW,
			);
		} catch (UnexpectedValueException) {
			self::addToAssertionCount(1);
			return;
		}

		self::fail('Expected ID token validation to be rejected');
	}

	private function nonceHash(): string
	{
		return hash('sha256', self::NONCE);
	}

	private function validator(): IdTokenValidator
	{
		return new IdTokenValidator();
	}

	private static function base64UrlEncode(string $value): string
	{
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}
}
