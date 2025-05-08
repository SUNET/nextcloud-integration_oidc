<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Listener;

use OCA\IOIDC\Db\IOIDCProviderMapper;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use Psr\Log\LoggerInterface;

/**
 * Class CSPListener
 *
 * @implements IEventListener
 */
class CSPListener implements IEventListener
{
    public function __construct(
        private IOIDCProviderMapper $ioidcProviderMapper,
        private LoggerInterface $logger
    ) {
    }

    public function handle(Event $event): void
    {
        $this->logger->debug('Adding CSP for OIDC', ['app' => 'integration_oidc']);
        if (!($event instanceof AddContentSecurityPolicyEvent)) {
            return;
        }
        $csp = new ContentSecurityPolicy();
        foreach ($this->ioidcProviderMapper-> query() as $provider) {
            $url = parse_url($provider->getAuthEndpoint());
            $http = $url["scheme"] . "://" . $url["host"];
            $csp->addAllowedFormActionDomain($http);
        }

        $event->addPolicy($csp);
    }
}
