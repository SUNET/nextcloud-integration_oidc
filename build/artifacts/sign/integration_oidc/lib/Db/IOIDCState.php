<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCP\AppFramework\Db\Entity;

class IOIDCState extends Entity
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
        $this->addType('createdAt', 'integer');
        $this->addType('id', 'integer');
        $this->addType('providerId', 'string');
        $this->addType('state', 'string');
        $this->addType('updatedAt', 'integer');
    }
}
