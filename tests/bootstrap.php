<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
	if (!str_starts_with($class, 'OCP\\')) {
		return;
	}

	$file = dirname(__DIR__) . '/vendor/nextcloud/ocp/' . str_replace('\\', '/', $class) . '.php';
	if (is_file($file)) {
		require_once $file;
	}
});
