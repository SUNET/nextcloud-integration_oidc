<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Security;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use JsonException;
use stdClass;
use UnexpectedValueException;

final class IdTokenValidator
{
    private const ALGORITHM = 'RS256';
    private const CLOCK_SKEW = 60;
    private const MAX_ID_TOKEN_LENGTH = 16_384;
    private const MAX_JWKS_LENGTH = 1_048_576;
    private const MAX_JWKS_KEYS = 100;
    private const MAX_KID_LENGTH = 256;
    private const MAX_RSA_MODULUS_BITS = 8192;
    private const PRIVATE_JWK_PARAMETERS = ['d', 'p', 'q', 'dp', 'dq', 'qi', 'oth', 'k'];

    /**
     * @param array<string, mixed> $jwks
     * @throws InvalidArgumentException If an expected value is invalid
     * @throws UnexpectedValueException If the token or key set is invalid
     */
    public function validate(
        string $idToken,
        array $jwks,
        string $expectedIssuer,
        string $clientId,
        string $expectedNonceHash,
        ?int $now = null,
    ): object {
        if ($expectedIssuer === '') {
            throw new InvalidArgumentException('Expected issuer must not be empty');
        }
        if ($clientId === '') {
            throw new InvalidArgumentException('Client ID must not be empty');
        }
        if (preg_match('/\A[0-9a-fA-F]{64}\z/D', $expectedNonceHash) !== 1) {
            throw new InvalidArgumentException('Expected nonce hash must be a SHA-256 hex digest');
        }
        if ($now !== null && ($now < 0 || $now > PHP_INT_MAX - self::CLOCK_SKEW)) {
            throw new InvalidArgumentException('Validation time is out of range');
        }

        $header = $this->parseHeader($idToken);
        $kid = $header->kid;
        $key = $this->parseKeySet($jwks, $kid);

        $validationTime = $now ?? time();
        $previousTimestamp = JWT::$timestamp;
        $previousLeeway = JWT::$leeway;
        JWT::$timestamp = $validationTime;
        JWT::$leeway = self::CLOCK_SKEW;

        try {
            try {
                $claims = JWT::decode($idToken, [$kid => $key]);
            } catch (UnexpectedValueException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new UnexpectedValueException('ID token could not be decoded', 0, $e);
            }
            $this->validateClaims(
                $claims,
                $expectedIssuer,
                $clientId,
                strtolower($expectedNonceHash),
                $validationTime,
            );

            return $claims;
        } finally {
            JWT::$timestamp = $previousTimestamp;
            JWT::$leeway = $previousLeeway;
        }
    }

    private function parseHeader(string $idToken): stdClass
    {
        if ($idToken === '' || strlen($idToken) > self::MAX_ID_TOKEN_LENGTH) {
            throw new UnexpectedValueException('ID token is empty or oversized');
        }

        $segments = explode('.', $idToken);
        if (count($segments) !== 3) {
            throw new UnexpectedValueException('ID token must contain three segments');
        }

        foreach ($segments as $segment) {
            $this->decodeBase64Url($segment, 'ID token segment');
        }

        try {
            $header = json_decode(
                $this->decodeBase64Url($segments[0], 'ID token header'),
                false,
                16,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new UnexpectedValueException('ID token header is not valid JSON', 0, $e);
        }

        if (!$header instanceof stdClass) {
            throw new UnexpectedValueException('ID token header must be a JSON object');
        }
        if (!property_exists($header, 'alg') || !is_string($header->alg) || $header->alg !== self::ALGORITHM) {
            throw new UnexpectedValueException('ID token algorithm must be RS256');
        }
        if (
            !property_exists($header, 'kid')
            || !is_string($header->kid)
            || trim($header->kid) === ''
            || strlen($header->kid) > self::MAX_KID_LENGTH
        ) {
            throw new UnexpectedValueException('ID token key ID is missing or invalid');
        }

        return $header;
    }

    /**
     * @param array<string, mixed> $jwks
     */
    private function parseKeySet(array $jwks, string $expectedKid): Key
    {
        if (!array_key_exists('keys', $jwks) || !is_array($jwks['keys']) || !array_is_list($jwks['keys'])) {
            throw new UnexpectedValueException('JWK Set must contain a list of keys');
        }
        if ($jwks['keys'] === [] || count($jwks['keys']) > self::MAX_JWKS_KEYS) {
            throw new UnexpectedValueException('JWK Set has an invalid number of keys');
        }

        try {
            $encodedJwks = json_encode($jwks, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnexpectedValueException('JWK Set is not valid JSON data', 0, $e);
        }
        if (strlen($encodedJwks) > self::MAX_JWKS_LENGTH) {
            throw new UnexpectedValueException('JWK Set is oversized');
        }

        $kids = [];
        $selectedJwk = null;
        foreach ($jwks['keys'] as $jwk) {
            if (!is_array($jwk) || $jwk === []) {
                throw new UnexpectedValueException('JWK must be a non-empty object');
            }

            $kid = $jwk['kid'] ?? null;
            if (!is_string($kid) || trim($kid) === '' || strlen($kid) > self::MAX_KID_LENGTH) {
                throw new UnexpectedValueException('JWK key ID is missing or invalid');
            }
            if (array_key_exists($kid, $kids)) {
                throw new UnexpectedValueException('JWK Set contains a duplicate key ID');
            }
            $kids[$kid] = true;

            foreach (self::PRIVATE_JWK_PARAMETERS as $parameter) {
                if (array_key_exists($parameter, $jwk)) {
                    throw new UnexpectedValueException('JWK must not contain private key material');
                }
            }

            if ($kid === $expectedKid) {
                $selectedJwk = $jwk;
            }
        }

        if ($selectedJwk === null) {
            throw new UnexpectedValueException('ID token references an unknown key ID');
        }
        if (($selectedJwk['kty'] ?? null) !== 'RSA') {
            throw new UnexpectedValueException('JWK key type must be RSA');
        }
        if (array_key_exists('use', $selectedJwk) && $selectedJwk['use'] !== 'sig') {
            throw new UnexpectedValueException('JWK use must be sig');
        }
        if (array_key_exists('key_ops', $selectedJwk) && $selectedJwk['key_ops'] !== ['verify']) {
            throw new UnexpectedValueException('JWK key operations must only allow verification');
        }
        if (array_key_exists('alg', $selectedJwk) && $selectedJwk['alg'] !== self::ALGORITHM) {
            throw new UnexpectedValueException('JWK algorithm must be RS256');
        }

        $this->requireJwkInteger($selectedJwk, 'n', intdiv(self::MAX_RSA_MODULUS_BITS, 8));
        $this->requireJwkInteger($selectedJwk, 'e', 8);

        try {
            $key = JWK::parseKey($selectedJwk, self::ALGORITHM);
        } catch (\Exception $e) {
            throw new UnexpectedValueException('JWK contains invalid RSA key material', 0, $e);
        }
        if (!$key instanceof Key) {
            throw new UnexpectedValueException('JWK does not contain a supported key');
        }

        $details = openssl_pkey_get_details($key->getKeyMaterial());
        if (
            $details === false
            || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA
            || !is_int($details['bits'] ?? null)
            || $details['bits'] < 2048
            || $details['bits'] > self::MAX_RSA_MODULUS_BITS
        ) {
            throw new UnexpectedValueException('JWK must contain a 2048 to 8192-bit RSA public key');
        }

        return $key;
    }

    /**
     * @param array<mixed> $jwk
     */
    private function requireJwkInteger(array $jwk, string $name, int $maxLength): string
    {
        if (!array_key_exists($name, $jwk) || !is_string($jwk[$name]) || $jwk[$name] === '') {
            throw new UnexpectedValueException(sprintf('JWK parameter "%s" is missing or invalid', $name));
        }

        $value = $this->decodeBase64Url($jwk[$name], sprintf('JWK parameter "%s"', $name));
        if ($value === '' || ord($value[0]) === 0) {
            throw new UnexpectedValueException(sprintf('JWK parameter "%s" is not a canonical integer', $name));
        }
        if (strlen($value) > $maxLength) {
            throw new UnexpectedValueException(sprintf('JWK parameter "%s" is oversized', $name));
        }

        return $value;
    }

    private function decodeBase64Url(string $value, string $field): string
    {
        if ($value === '' || preg_match('/\A[A-Za-z0-9_-]+\z/D', $value) !== 1 || strlen($value) % 4 === 1) {
            throw new UnexpectedValueException($field . ' is not valid base64url');
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4),
            true,
        );
        if ($decoded === false || rtrim(strtr(base64_encode($decoded), '+/', '-_'), '=') !== $value) {
            throw new UnexpectedValueException($field . ' is not canonical base64url');
        }

        return $decoded;
    }

    private function validateClaims(
        object $claims,
        string $expectedIssuer,
        string $clientId,
        string $expectedNonceHash,
        int $now,
    ): void {
        $issuer = $this->requireStringClaim($claims, 'iss');
        if ($issuer !== $expectedIssuer) {
            throw new UnexpectedValueException('ID token issuer does not match');
        }

        $this->requireStringClaim($claims, 'sub');

        if (!property_exists($claims, 'aud')) {
            throw new UnexpectedValueException('ID token audience is missing');
        }
        $audiences = is_string($claims->aud) ? [$claims->aud] : $claims->aud;
        if (!is_array($audiences) || $audiences === [] || !array_is_list($audiences)) {
            throw new UnexpectedValueException('ID token audience is invalid');
        }
        foreach ($audiences as $audience) {
            if (!is_string($audience) || $audience === '') {
                throw new UnexpectedValueException('ID token audience is invalid');
            }
        }
        if (!in_array($clientId, $audiences, true)) {
            throw new UnexpectedValueException('ID token audience does not include the client ID');
        }

        if (count($audiences) > 1 || property_exists($claims, 'azp')) {
            $authorizedParty = $this->requireStringClaim($claims, 'azp');
            if ($authorizedParty !== $clientId) {
                throw new UnexpectedValueException('ID token authorized party does not match');
            }
        }

        $expiration = $this->requireNumericDateClaim($claims, 'exp');
        if ($now - self::CLOCK_SKEW >= $expiration) {
            throw new UnexpectedValueException('ID token has expired');
        }

        $issuedAt = $this->requireNumericDateClaim($claims, 'iat');
        if (floor($issuedAt) > $now + self::CLOCK_SKEW) {
            throw new UnexpectedValueException('ID token was issued in the future');
        }

        $nonce = $this->requireStringClaim($claims, 'nonce');
        if (!hash_equals($expectedNonceHash, hash('sha256', $nonce))) {
            throw new UnexpectedValueException('ID token nonce does not match');
        }
    }

    private function requireStringClaim(object $claims, string $name): string
    {
        if (!property_exists($claims, $name) || !is_string($claims->{$name}) || $claims->{$name} === '') {
            throw new UnexpectedValueException(sprintf('ID token claim "%s" is missing or invalid', $name));
        }

        return $claims->{$name};
    }

    private function requireNumericDateClaim(object $claims, string $name): int|float
    {
        if (
            !property_exists($claims, $name)
            || (!is_int($claims->{$name}) && !is_float($claims->{$name}))
            || !is_finite((float)$claims->{$name})
            || $claims->{$name} < 0
        ) {
            throw new UnexpectedValueException(sprintf('ID token claim "%s" is missing or invalid', $name));
        }

        return $claims->{$name};
    }
}
