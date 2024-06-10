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
class Version010000Date20240530151450 extends SimpleMigrationStep
{
  public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
  {
    /** @var ISchemaWrapper $schema */
    $schema = $schemaClosure();

    if (!$schema->hasTable('ioidc_providers')) {
      $schema->createTable('ioidc_providers');
      $table = $schema->getTable('ioidc_providers');
      $table->addColumn('id', Types::INTEGER, ['notnull' => true, 'autoincrement' => true]);
      $table->addColumn('auth_endpoint', Types::STRING, ['notnull' => true]);
      $table->addColumn('client_id', Types::STRING, ['notnull' => true]);
      $table->addColumn('client_secret', Types::STRING, ['notnull' => true]);
      $table->addColumn('name', Types::STRING, ['notnull' => true]);
      $table->addColumn('scope', Types::STRING, ['notnull' => true]);
      $table->addColumn('revoke_endpoint', Types::STRING, ['notnull' => true]);
      $table->addColumn('token_endpoint', Types::STRING, ['notnull' => true]);
      $table->addColumn('user_endpoint', Types::STRING, ['notnull' => true]);
      $table->setPrimaryKey(['id']);
    }
    if (!$schema->hasTable('ioidc_userconfig')) {
      $schema->createTable('ioidc_userconfig');
      $table = $schema->getTable('ioidc_userconfig');
      $table->addColumn('id', Types::INTEGER, ['notnull' => true, 'autoincrement' => true]);
      $table->addColumn('access_token', Types::STRING, ['notnull' => true]);
      $table->addColumn('email', Types::STRING, ['notnull' => false]);
      $table->addColumn('expires_in', Types::INTEGER, ['notnull' => true]);
      $table->addColumn('provider_id', Types::INTEGER, ['notnull' => true]);
      $table->addColumn('refresh_token', Types::STRING, ['notnull' => true]);
      $table->addColumn('scope', Types::STRING, ['notnull' => true]);
      $table->addColumn('sub', Types::STRING, ['notnull' => false]);
      $table->addColumn('timestamp', Types::INTEGER, ['notnull' => true]);
      $table->addColumn('token_type', Types::STRING, ['notnull' => true]);
      $table->addColumn('uid', Types::STRING, ['notnull' => true]);
      $table->setPrimaryKey(['id']);
    }
    if (!$schema->hasTable('ioidc_stateconfig')) {
      $schema->createTable('ioidc_stateconfig');
      $table = $schema->getTable('ioidc_stateconfig');
      $table->addColumn('id', Types::INTEGER, ['notnull' => true, 'autoincrement' => true]);
      $table->addColumn('uid', Types::STRING, ['notnull' => true]);
      $table->addColumn('provider_id', Types::INTEGER, ['notnull' => true]);
      $table->addColumn('state', Types::STRING, ['notnull' => true]);
      $table->setPrimaryKey(['id']);
    }

    return $schema;
  }
}
