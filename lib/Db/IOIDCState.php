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
 * @method getCreatedAt(): int
 * @method setCreatedAt($int)
 * @method getProviderId(): string
 * @method setProviderId($string): void
 * @method getState(): string
 * @method setState($string): void
 * @method getUpdatedAt(): int
 * @method setUpdatedAt($int)
 */

class IOIDCState extends Entity implements JsonSerializable
{
    /**
     * @var $int $createdAt
     */
    protected $createdAt;
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
    /**
     * @var $int $updatedAt
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function jsonSerialize(): mixed
    {
        return [
            'createdAt' => $this->createdAt,
            'id' => $this->id,
            'providerId' => $this->providerId,
            'state' => $this->state,
            'uid' => $this->uid,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
