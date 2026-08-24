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
 * @method getProviderId(): string
 * @method setProviderId($string): void
 * @method getState(): string
 * @method setState($string): void
 * @method getUid(): string
 * @method setUid($string): void
 * @method getNonceHash(): string
 * @method setNonceHash(string $nonceHash): void
 * @method getCreatedAt(): int
 * @method setCreatedAt(int $createdAt): void
 * @method getProviderVersion(): int
 * @method setProviderVersion(int $providerVersion): void
 */

class IOIDCState extends Entity implements JsonSerializable
{
    /**
     * @var string $uid
     */
    protected $uid;
    /**
     * @var string $providerId
     */
    protected $providerId;
    /**
     * @var string $state
     */
    protected $state;
    /** @var string */
    protected $nonceHash;
    /** @var int */
    protected $createdAt;
    /** @var int */
    protected $providerVersion;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('providerVersion', 'integer');
    }
    /**
     * @param array $params
     * @return IOIDCState
     */
    public function setParams(array $params): IOIDCState
    {
        $this->setState($params['state']);
        $this->setProviderId($params['providerId']);
        $this->setUid($params['uid']);
        $this->setNonceHash($params['nonceHash']);
        $this->setCreatedAt($params['createdAt']);
        $this->setProviderVersion($params['providerVersion']);

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'providerId' => $this->getProviderId(),
            'state' => $this->getState(),
            'uid' => $this->getUid(),
            'createdAt' => $this->getCreatedAt(),
            'providerVersion' => $this->getProviderVersion(),
        ];
    }
}
