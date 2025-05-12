<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method ?string getAccessType()
 * @method ?string getDisplay()
 * @method ?string getDomainHint()
 * @method ?string getHd()
 * @method ?string getIncludeGrantedScopes()
 * @method ?string getLoginHint()
 * @method ?string getName()
 * @method ?string getResponseMode()
 * @method ?string getResponseType()
 * @method ?string getTenant()
 * @method string getAuthEndpoint()
 * @method string getClientId()
 * @method string getClientSecret()
 * @method string getPrompt()
 * @method string getScope()
 * @method string getTokenEndpoint()
 * @method string getUserEndpoint()
 * @method void setAccessType(?string $accessType)
 * @method void setAuthEndpoint(string $authEndpoint)
 * @method void setClientId(string $clientId)
 * @method void setClientSecret(string $clientSecret)
 * @method void setDisplay(?string $display)
 * @method void setDomainHint(?string $domainHint)
 * @method void setHd(?string $hd)
 * @method void setIncludeGrantedScopes(?string $includeGrantedScopes)
 * @method void setLoginHint(?string $loginHint)
 * @method void setName(?string $name)
 * @method void setPrompt(string $prompt)
 * @method void setResponseMode(?string $responseMode)
 * @method void setResponseType(?string $responseType)
 * @method void setScope(string $scope)
 * @method void setTenant(?string $tenant)
 * @method void setTokenEndpoint(string $tokenEndpoint)
 * @method void setUserEndpoint(string $userEndpoint)
 *
 */
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
     * @var ?string $tenant
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
    /**
     * @param array $params
     * @return IOIDCProvider
     */
    public function setParams(array $params): IOIDCProvider
    {
        $this->setAccessType($params['accessType']);
        $this->setAuthEndpoint($params['authEndpoint']);
        $this->setClientId($params['clientId']);
        $this->setClientSecret($params['clientSecret']);
        $this->setDisplay($params['display']);
        $this->setDomainHint($params['domainHint']);
        $this->setHd($params['hd']);
        $this->setIncludeGrantedScopes($params['includeGrantedScopes']);
        $this->setLoginHint($params['loginHint']);
        $this->setName($params['name']);
        $this->setPrompt($params['prompt']);
        $this->setResponseMode($params['responseMode']);
        $this->setResponseType($params['responseType']);
        $this->setScope($params['scope']);
        $this->setTenant($params['tenant']);
        $this->setTokenEndpoint($params['tokenEndpoint']);
        $this->setUserEndpoint($params['userEndpoint']);

        return $this;
    }
}
