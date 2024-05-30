<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new table for the integration_oidc app.
 */
class Version010000Date20240530151450 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

    if(! $schema->hasTable('ioidc_providers')){
      $schema->createTable('ioidc_providers');
      $table = $schema->getTable('ioidc_providers');
      $table->addColumn('id', Types::INTEGER, ['notnull' => true, 'autoincrement' => true]);
      $table->addColumn('name', Types::STRING, ['notnull' => true]);
      $table->addColumn('client_id', Types::STRING, ['notnull' => true]);
      $table->addColumn('client_secret', Types::STRING, ['notnull' => true]);
      $table->addColumn('token_endpoint', Types::STRING, ['notnull' => true]);
      $table->setPrimaryKey(['id']);
    }

		return $schema;
	}
}