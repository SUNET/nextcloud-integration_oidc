<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Fix column types before migration 013000 runs, which fails because it
 * tries to set String length to 65535 (exceeding Nextcloud's 4000 limit).
 *
 * Also prepare the email column for 013000's NOT NULL change by setting
 * a default value and backfilling existing NULLs.
 *
 * See: https://github.com/SUNET/nextcloud-integration_oidc/issues/11
 */
class Version012000Date20260330000000 extends SimpleMigrationStep
{
    private IDBConnection $connection;

    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('ioidc_userconfig')) {
            $table = $schema->getTable('ioidc_userconfig');
            $table->modifyColumn('access_token', ['type' => Type::getType(Types::TEXT)]);
            $table->modifyColumn('refresh_token', ['type' => Type::getType(Types::TEXT)]);
            $table->modifyColumn('email', ['default' => '', 'length' => 320]);
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        $qb = $this->connection->getQueryBuilder();
        $qb->update('ioidc_userconfig')
            ->set('email', $qb->createNamedParameter(''))
            ->where($qb->expr()->isNull('email'))
            ->executeStatement();
    }
}
