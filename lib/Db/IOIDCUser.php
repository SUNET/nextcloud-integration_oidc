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
 * @method getCode(): string
 * @method setCode(string $accessToken): void
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
     * @var string $code
     */
    protected $code;
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
    /**
     * @param array $params
     * @return IOIDCUser
     */
    public function setParams(array $params): IOIDCUser
    {
        $this->setAccessToken($params['accessToken']);
        $this->setEmail($params['email']);
        $this->setExpiresIn($params['expiresIn']);
        $this->setRefreshToken($params['refreshToken']);
        $this->setScope($params['scope']);
        $this->setSub($params['sub']);
        $this->setTimestamp($params['timestamp']);
        $this->setTokenType($params['tokenType']);
        $this->setProviderId($params['providerId']);
        $this->setUid($params['uid']);

        return $this;
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
