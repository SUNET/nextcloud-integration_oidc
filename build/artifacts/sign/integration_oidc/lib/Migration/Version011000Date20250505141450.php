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
class Version011000Date20250505141450 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('ioidc_providers')) {
            $table = $schema->getTable('ioidc_providers');
            $table->addColumn('access_type', Types::STRING, ['notnull' => true]);
            $table->addColumn('display', Types::STRING, ['notnull' => true]);
            $table->addColumn('domain_hint', Types::STRING, ['notnull' => true]);
            $table->addColumn('hd', Types::STRING, ['notnull' => true]);
            $table->addColumn('include_granted_scopes', Types::STRING, ['notnull' => true]);
            $table->addColumn('login_hint', Types::STRING, ['notnull' => true]);
            $table->addColumn('prompt', Types::STRING, ['notnull' => true]);
            $table->addColumn('response_mode', Types::STRING, ['notnull' => true]);
            $table->addColumn('response_type', Types::STRING, ['notnull' => true]);
            $table->addColumn('tenant', Types::STRING, ['notnull' => true]);
        }

        return $schema;
    }
}
