<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method getAccessToken(): string
 * @method setAccessToken(string $accessToken): void
 * @method getEmail(): string
 * @method setEmail(string $email): void
 * @method getExpiresIn(): int
 * @method setExpiresIn(int $expiresIn): void
 * @method getProviderId(): string
 * @method setProviderId(string $providerId): void
 * @method getRefreshToken(): string
 * @method setRefreshToken(string $refreshToken): void
 * @method getScope(): string
 * @method setScope(string $scope): void
 * @method getSub(): string
 * @method setSub(string $sub): void
 * @method getTimestamp(): int
 * @method setTimestamp(int $timestamp): void
 * @method getTokenType(): string
 * @method setTokenType(string $tokenType): void
 * @method getUid(): string
 * @method setUid(string $uid): void
       
 */

class IOIDCUser extends Entity implements JsonSerializable
{
    /**
     * @var string $accessToken
     */
    protected $accessToken;
    /**
     * @var string email
     */
    protected $email;
    /**
     * @var int $expiresIn
     */
    protected $expiresIn;
    /**
     * @var string  $idProvider
     */
    protected $providerId;
    /**
     * @var string $refreshToken
     */
    protected $refreshToken;
    /**
     * @var string $scope
     */
    protected $scope;
    /**
     * @var string $sub
     */
    protected $sub;
    /**
     * @var int $timestamp
     */
    protected $timestamp;
    /**
     * @var string $tokenType
     */
    protected $tokenType;
    /**
     * @var string $uid
     */
    protected $uid;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('expires_in', 'integer');
        $this->addType('timestamp', 'integer');
    }

    public function jsonSerialize(): mixed
    {
        return [
            'access_token' => $this->accessToken,
            'email' => $this->email,
            'expiresIn' => $this->expiresIn,
            'id' => $this->id,
            'providerId' => $this->providerId,
            'refreshToken' => $this->refreshToken,
            'scope' => $this->scope,
            'sub' => $this->sub,
            'timestamp' => $this->timestamp,
            'tokenType' => $this->tokenType,
            'uid' => $this->uid,
        ];
    }
}
