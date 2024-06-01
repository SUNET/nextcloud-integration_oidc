<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Mikael Nordin <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\IOIDC\AppInfo;

use OCA\IOIDC\Listener\CSPListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App
{
  public const APP_ID = 'integration_oidc';

  public function __construct()
  {
    parent::__construct(self::APP_ID);
  }
  public function register(IRegistrationContext $context): void
  {
    $context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPListener::class);
  }
  public function boot(IBootContext $context): void
  {
  }
}
