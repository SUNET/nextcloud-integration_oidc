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
 * @method setAccessType(string $accessType): void
 * @method setAuthEndpoint(string $authEndpoint): void
 * @method setClientId(string $clientId): void
 * @method setClientSecret(string $clientSecret): void
 * @method setDisplay(string $display): void
 * @method setDomainHint(string $domainHint): void
 * @method setHd(string $hd): void
 * @method setIncludeGrantedScopes(string $includeGrantedScopes): void
 * @method setLoginHint(string $loginHint): void
 * @method setName(string $name): void
 * @method setPrompt(string $prompt): void
 * @method setResponseMode(string $responseMode): void
 * @method setResponseType(string $responseType): void
 * @method setRevokeEndpoint(string $revokeEndpoint): void
 * @method setScope(string $scope): void
 * @method setTenant(string $tenant): void
 * @method setTokenEndpoint(string $tokenEndpoint): void
 * @method setUserEndpoint(string $userEndpoint): void
 * @method getAccessType(): string
 * @method getDisplay(): string
 * @method getDomainHint(): string
 * @method getHd(): string
 * @method getIncludeGrantedScopes(): string
 * @method getLoginHint(): string
 * @method getName(): string
 * @method getResponseMode(): string
 * @method getResponseType(): string
 * @method getTenant(): string
 * @method getAuthEndpoint(): string
 * @method getClientId(): string
 * @method getClientSecret(): string
 * @method getPrompt(): string
 * @method getScope(): string
 * @method getEndpoint(): string
 * @method getTokenEndpoint(): string
 * @method getUserEndpoint(): string
 *
 */
class IOIDCProvider extends Entity implements JsonSerializable
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
     * @var string $revokeEndpoint
     */
    protected $revokeEndpoint;
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
        $this->addType('id', 'integer');
    }
    /**
     * @param array $params
     * @return IOIDCProvider
     */
    public function setParams(array $params): IOIDCProvider
    {
        $this->setAccessType($params['access_type']);
        $this->setAuthEndpoint($params['auth_endpoint']);
        $this->setClientId($params['client_id']);
        $this->setClientSecret($params['client_secret']);
        $this->setDisplay($params['display']);
        $this->setDomainHint($params['domain_hint']);
        $this->setHd($params['hd']);
        $this->setIncludeGrantedScopes($params['include_granted_scopes']);
        $this->setLoginHint($params['login_hint']);
        $this->setName($params['name']);
        $this->setPrompt($params['prompt']);
        $this->setResponseMode($params['response_mode']);
        $this->setResponseType($params['response_type']);
        $this->setRevokeEndpoint($params['revoke_endpoint']);
        $this->setScope($params['scope']);
        $this->setTenant($params['tenant']);
        $this->setTokenEndpoint($params['token_endpoint']);
        $this->setUserEndpoint($params['user_endpoint']);

        return $this;
    }
    public function jsonSerialize(): array
    {
        $data = array(
            'accessType' => $this->getAccessType(),
            'authEndpoint' => $this->getAuthEndpoint(),
            'clientId' => $this->getClientId(),
            'clientSecret' => $this->getClientSecret(),
            'display' => $this->getDisplay(),
            'domainHint' => $this->getDomainHint(),
            'hd' => $this->getHd(),
            'id' => $this->getId(),
            'includeGrantedScopes' => $this->getIncludeGrantedScopes(),
            'loginHint' => $this->getLoginHint(),
            'name' => $this->getName(),
            'prompt' => $this->getPrompt(),
            'responseMode' => $this->getResponseMode(),
            'responseType' => $this->getResponseType(),
            'revokeEndpoint' => $this->getRevokeEndpoint(),
            'scope' => $this->getScope(),
            'tenant' => $this->getTenant(),
            'tokenEndpoint' => $this->getTokenEndpoint(),
            'userEndpoint' => $this->getUserEndpoint(),
        );
        return $data;
    }
}
