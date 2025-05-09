<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<IOIDCState>
 */
class IOIDCStateMapper extends QBMapper
{
    public const TABLE_NAME = 'ioidc_stateconfig';

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::TABLE_NAME);
    }
    /**
     * @param array $params
     * @return int
     */
    public function register_state(array $params)
    {
        $entity = $this->mapRowToEntity($params);
        $this->insert($entity);
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
     * @param String $uid
     * @param String $state
     * @return mixed
     */
    public function query_state(String $uid, String $state): mixed
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
            $qb->createNamedParameter($state)
        );

        $rows = $qb->select('s.id', 's.provider_id', 's.state', 's.uid', 'p.name', 'p.token_endpoint', 'p.client_id', 'p.client_secret')
          ->from('ioidc_stateconfig', 's')
          ->where($expr)
          ->andWhere($and_expr)
          ->innerJoin('s', 'ioidc_providers', 'p', 's.provider_id = p.id')
          ->executeQuery();

        return $rows->fetchAll()[0];
    }
}
