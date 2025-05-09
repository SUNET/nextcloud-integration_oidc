<?php

namespace OCA\IOIDC\Settings;

use OCP\Settings\IIconSection;
use OCP\IURLGenerator;
use OCP\IL10N;

class AdminSection implements IIconSection
{
  public function __construct(
    private IURLGenerator $urlGenerator,
		private IL10N         $l
  ) {
  }


  public function getID(): string
  {
    return 'connected-accounts';
  }
	public function getName(): string {
		return $this->l->t('Connected accounts');
	}

  public function getPriority(): int
  {
    return 90;
  }
  public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'categories/integration.svg');
	}
}
