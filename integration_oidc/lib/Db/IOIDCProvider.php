<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCP\AppFramework\Db\Entity;

class IOIDCProvider extends Entity
{
    /**
     * @var ?string $accessType
     */
    protected $accessType;
    /**
     * @var string $authEndpoint
     */
    protected $authEndpoint;
    /**
     * @var string $clientId
     */
    protected $clientId;
    /**
     * @var string $clientSecret
     */
    protected $clientSecret;
    /**
     * @var ?string $display
     */
    protected $display;
    /**
     * @var ?string $domainHint
     */
    protected $domainHint;
    /**
     * @var ?string $hd
     */
    protected $hd;
    /**
     * @var ?string $includeGrantedScopes
     */
    protected $includeGrantedScopes;
    /**
     * @var ?string $loginHint
     */
    protected $loginHint;
    /**
     * @var string $name
     */
    protected $name;
    /**
     * @var ?string $prompt
     */
    protected $prompt;
    /**
     * @var ?string $responseMode
     */
    protected $responseMode;
    /**
     * @var ?string $responseType
     */
    protected $responseType;
    /**
     * @var string $scope
     */
    protected $scope;
    /**
     * @var string $tenant
     */
    protected $tenant;
    /**
     * @var string $tokenEndpoint
     */
    protected $tokenEndpoint;
    /**
     * @var string $userEndpoint
     */
    protected $userEndpoint;

    public function __construct()
    {
        $this->addType('accessType', 'string');
        $this->addType('authEndpoint', 'string');
        $this->addType('clientId', 'string');
        $this->addType('clientSecret', 'string');
        $this->addType('display', 'string');
        $this->addType('domainHint', 'string');
        $this->addType('hd', 'string');
        $this->addType('includeGrantedScopes', 'string');
        $this->addType('loginHint', 'string');
        $this->addType('name', 'string');
        $this->addType('prompt', 'string');
        $this->addType('responseMode', 'string');
        $this->addType('responseType', 'string');
        $this->addType('scope', 'string');
        $this->addType('tenant', 'string');
        $this->addType('tokenEndpoint', 'string');
        $this->addType('userEndpoint', 'string');
    }
}
