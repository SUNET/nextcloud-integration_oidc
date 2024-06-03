<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Listener;

use OCA\IOIDC\Db\IOIDCConnection;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use Psr\Log\LoggerInterface;

class CSPListener implements IEventListener
{
  public function __construct(
    private  IOIDCConnection $ioidcConnection,
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
    foreach ($this->ioidcConnection->query() as $provider) {
      $url = parse_url($provider['auth_endpoint']);
      $http = $url["scheme"] . "://" . $url["host"];
      $csp->addAllowedFormActionDomain($http);
    }

    $event->addPolicy($csp);
  }
}
