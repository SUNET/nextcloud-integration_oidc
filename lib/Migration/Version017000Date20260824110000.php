<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version017000Date20260824110000 extends SimpleMigrationStep
{
    public function __construct(private IDBConnection $connection) {}

    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        // Existing browser-generated transactions cannot be made trustworthy retroactively.
        $this->connection->getQueryBuilder()
            ->delete('ioidc_stateconfig')
            ->executeStatement();

    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $providers = $schema->getTable('ioidc_providers');
        if (!$providers->hasColumn('issuer')) {
            $providers->addColumn('issuer', Types::STRING, ['notnull' => false, 'length' => 255]);
        }
        if (!$providers->hasColumn('jwks_uri')) {
            $providers->addColumn('jwks_uri', Types::STRING, ['notnull' => false, 'length' => 2000]);
        }
        if (!$providers->hasColumn('token_endpoint_auth_method')) {
            $providers->addColumn('token_endpoint_auth_method', Types::STRING, ['notnull' => true, 'default' => 'client_secret_post', 'length' => 32]);
        }
        if (!$providers->hasColumn('config_version')) {
            $providers->addColumn('config_version', Types::INTEGER, ['notnull' => true, 'default' => 1]);
        }
        $providers->modifyColumn('revoke_endpoint', ['notnull' => false]);
        $providers->modifyColumn('user_endpoint', ['notnull' => false]);
        $providers->modifyColumn('prompt', ['notnull' => false]);

        $states = $schema->getTable('ioidc_stateconfig');
        if (!$states->hasColumn('nonce_hash')) {
            $states->addColumn('nonce_hash', Types::STRING, ['notnull' => true, 'length' => 64]);
        }
        if (!$states->hasColumn('created_at')) {
            $states->addColumn('created_at', Types::INTEGER, ['notnull' => true]);
        }
        if (!$states->hasColumn('provider_version')) {
            $states->addColumn('provider_version', Types::INTEGER, ['notnull' => true]);
        }
        if (!$states->hasIndex('ioidc_state_uid_hash')) {
            $states->addUniqueIndex(['uid', 'state'], 'ioidc_state_uid_hash');
        }
        if (!$states->hasIndex('ioidc_state_created')) {
            $states->addIndex(['created_at'], 'ioidc_state_created');
        }

        $users = $schema->getTable('ioidc_userconfig');
        if (!$users->hasColumn('provider_version')) {
            $users->addColumn('provider_version', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        }
        if (!$users->hasColumn('revision')) {
            $users->addColumn('revision', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        }
        if (!$users->hasColumn('connection_key')) {
            $users->addColumn('connection_key', Types::STRING, ['notnull' => false, 'length' => 64]);
        }
        $users->modifyColumn('email', ['notnull' => false]);
        if (!$users->hasIndex('ioidc_user_connection')) {
            $users->addUniqueIndex(['connection_key'], 'ioidc_user_connection');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        $qb = $this->connection->getQueryBuilder();
        $result = $qb->select('id', 'uid', 'provider_id', 'connection_key')
            ->from('ioidc_userconfig')
            ->orderBy('id', 'DESC')
            ->executeQuery();
        $rows = $result->fetchAll();
        $result->closeCursor();

        $groups = [];
        foreach ($rows as $row) {
            $group = hash('sha256', (string)$row['uid'] . "\0" . (string)$row['provider_id']);
            $groups[$group][] = $row;
        }
        foreach ($groups as $connectionKey => $connections) {
            $hasActiveConnection = false;
            foreach ($connections as $connection) {
                if (is_string($connection['connection_key']) && $connection['connection_key'] !== '') {
                    $hasActiveConnection = true;
                    break;
                }
            }
            if ($hasActiveConnection) {
                continue;
            }

            $update = $this->connection->getQueryBuilder();
            $update->update('ioidc_userconfig')
                ->set('connection_key', $update->createNamedParameter($connectionKey))
                ->where($update->expr()->eq('id', $update->createNamedParameter((int)$connections[0]['id'])))
                ->executeStatement();
        }
    }

}
