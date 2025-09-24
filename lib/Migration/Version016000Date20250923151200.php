<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Fix type of columns for the integration_oidc app.
 */
class Version016000Date20250923151200 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('ioidc_userconfig')) {
            $table = $schema->getTable('ioidc_userconfig');
            $table->modifyColumn('access_token', ['type' => Type::getType(Types::TEXT)]);
            $table->modifyColumn('refresh_token', ['type' => Type::getType(Types::TEXT)]);
        }

        return $schema;
    }
}
