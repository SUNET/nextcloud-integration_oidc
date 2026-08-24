<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<IOIDCState>
 */
class IOIDCStateMapper extends QBMapper
{
    public const TABLE_NAME = 'ioidc_stateconfig';
    public const STATE_TTL = 600;
    private LoggerInterface $logger;

    public function __construct(
        IDBConnection $db,
        LoggerInterface $logger
    ) {
        parent::__construct($db, self::TABLE_NAME);
        $this->logger = $logger;
    }
    public function delete_userstate(array $params): void
    {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq(
                    'uid',
                    $qb->createNamedParameter($params['uid'])
                )
            )
            ->andWhere(
                $qb->expr()->eq(
                    'provider_id',
                    $qb->createNamedParameter($params['provider_id'])
                )
            );
        $entities = $this->findEntities($query);
        foreach ($entities as $entity) {
            $this->delete($entity);
        }
    }
    /**
     * @param array $params
     * @return int
     */
    public function register_state(array $params): int
    {
        $this->deleteExpired((int)$params['createdAt']);
        $entity = new IOIDCState();
        $entity->setParams($params);
        $entity = $this->insert($entity);
        return $entity->getId();
    }
    public function get(int $id): IOIDCState
    {
        $query = $this->db->getQueryBuilder()->select('*')
            ->from(self::TABLE_NAME)
            ->where('id = :id')
            ->setParameter(':id', $id);
        $entity = $this->findEntity($query);
        return $entity;
    }
    /**
     * Atomically consume a state before any outbound token request is made.
     *
     * @return array<string, mixed>
     */
    public function consume_state(string $uid, string $state, int $now): array
    {
        /**
         * @var IQueryBuilder $qb
         * */
        $qb = $this->db->getQueryBuilder();
        $expr = $qb->expr()->eq(
            's.uid',
            $qb->createNamedParameter($uid)
        );
        $and_expr = $qb->expr()->eq(
            's.state',
            $qb->createNamedParameter(hash('sha256', $state))
        );

        $rows = $qb->select(
            's.id',
            's.provider_id',
            's.nonce_hash',
            's.created_at',
            's.provider_version',
            'p.config_version',
            'p.name',
            'p.issuer',
            'p.jwks_uri',
            'p.token_endpoint',
            'p.client_id',
            'p.client_secret',
            'p.token_endpoint_auth_method',
            'p.scope'
        )
            ->from('ioidc_stateconfig', 's')
            ->where($expr)
            ->andWhere($and_expr)
            ->innerJoin('s', 'ioidc_providers', 'p', 's.provider_id = p.id')
            ->executeQuery();

        $result = $rows->fetchAll();
        $rows->closeCursor();
        if ($result === []) {
            throw new \UnexpectedValueException('OIDC state is invalid');
        }

        $transaction = $result[0];
        $delete = $this->db->getQueryBuilder();
        $deleted = $delete->delete(self::TABLE_NAME)
            ->where($delete->expr()->eq('id', $delete->createNamedParameter((int)$transaction['id'])))
            ->andWhere($delete->expr()->eq('uid', $delete->createNamedParameter($uid)))
            ->andWhere($delete->expr()->eq('state', $delete->createNamedParameter(hash('sha256', $state))))
            ->executeStatement();
        if ($deleted !== 1) {
            throw new \UnexpectedValueException('OIDC state has already been consumed');
        }
        if ((int)$transaction['created_at'] < $now - self::STATE_TTL) {
            throw new \UnexpectedValueException('OIDC state has expired');
        }
        if ((int)$transaction['provider_version'] !== (int)$transaction['config_version']) {
            throw new \UnexpectedValueException('OIDC provider changed while authorization was in progress');
        }

        return $transaction;
    }

    private function deleteExpired(int $now): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TABLE_NAME)
            ->where($qb->expr()->lt('created_at', $qb->createNamedParameter($now - self::STATE_TTL)))
            ->executeStatement();
    }
}
