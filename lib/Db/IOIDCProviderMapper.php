<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCA\IOIDC\Db\IOIDCProvider;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<IOIDCProvider>
 */
class IOIDCProviderMapper extends QBMapper
{
    public const TABLE_NAME = 'ioidc_providers';

    public function __construct(
        IDBConnection $db,
    ) {
        parent::__construct($db, self::TABLE_NAME);
    }
    /**
     * @return array
     */
    public function query(): array
    {
        /**
         * @var IQueryBuilder $qb
         * */
        $qb = $this->db->getQueryBuilder();
        $response = array();

        $query = $qb->select('*')
            ->from(self::TABLE_NAME);
        $entities = $this->findEntities($query);
        foreach ($entities as $entity) {
            array_push($response, array($entity));
        }
        return $entities;
    }
    /**
     * @param array $params
     * @return int
     */
    public function register(array $params): int
    {
        $entity = new IOIDCProvider();
        $entity->setParams($params);
        $entity = $this->insert($entity);
        return $entity->getId();
    }
}
