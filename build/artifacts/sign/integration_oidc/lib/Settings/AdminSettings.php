<?php

namespace OCA\IOIDC\Settings;

use OCA\IOIDC\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings
{
  public function __construct(
    private IInitialState $initialStateService,
  ) {
  }

  /**
   * @return TemplateResponse
   */
  public function getForm(): TemplateResponse
  {
    //$this->initialStateService->provideInitialState('admin-config', $adminConfig);
    return new TemplateResponse(Application::APP_ID, 'adminSettings');
  }

  public function getSection(): string
  {
    return 'connected-accounts';
  }

  public function getPriority(): int
  {
    return 10;
  }
}
