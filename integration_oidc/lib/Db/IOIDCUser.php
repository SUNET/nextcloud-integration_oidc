<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCP\AppFramework\Db\Entity;

class IOIDCUser extends Entity
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
        $this->addType('accessToken', 'string');
        $this->addType('email', 'string');
        $this->addType('expiresIn', 'int');
        $this->addType('providerId', 'string');
        $this->addType('refreshToken', 'string');
        $this->addType('scope', 'string');
        $this->addType('sub', 'string');
        $this->addType('timestamp', 'int');
        $this->addType('tokenType', 'string');
        $this->addType('uid', 'string');
    }
}
