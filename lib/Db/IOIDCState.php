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

    public function __construct()
    {
        $this->addType('id', 'integer');
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

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'providerId' => $this->getProviderId(),
            'state' => $this->getState(),
            'uid' => $this->getUid(),
        ];
    }
}
