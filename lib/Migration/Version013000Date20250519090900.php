<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new columns for the integration_oidc app.
 */
class Version013000Date20250519090900 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('ioidc_userconfig')) {
            $table = $schema->getTable('ioidc_userconfig');
            $table->modifyColumn('access_token', ['notnull' => true, 'length' => 65535]);
            $table->modifyColumn('email', ['notnull' => true, 'length' => 320]);
            $table->modifyColumn('refresh_token', ['notnull' => true, 'length' => 65535]);
            $table->addColumn('code', Types::STRING, ['notnull' => false]);
        }

        return $schema;
    }
}
