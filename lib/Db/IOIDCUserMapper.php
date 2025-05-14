<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IOIDC\Db;

use OCA\IOIDC\Db\IOIDCUser;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<IOIDCUser>
 */
class IOIDCUserMapper extends QBMapper
{
    public const TABLE_NAME = 'ioidc_userconfig';

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::TABLE_NAME);
    }
    /**
     * @param array $params
     * @return int
     */
    public function register_user(array $params): int
    {
        $entity = new IOIDCUser();
        $entity = $entity->setParams($params);
        $this->insert($entity);
        return $entity->getId();
    }
    /**
     * @param array $params
     * @return void
     */
    public function refresh_token(array $params): void
    {

        /**
         * @var IQueryBuilder $qb
         * */
        $qb = $this->db->getQueryBuilder();
        $qb->update('ioidc_userconfig', 'u')
            ->set('u.access_token', $qb->createNamedParameter($params['access_token']))
            ->set('u.expires_in', $qb->createNamedParameter($params['expires_in']))
            ->set('u.timestamp', $qb->createNamedParameter(time()))
            ->set('u.prompt', $qb->createNamedParameter($params['prompt']))
            ->set('u.scope', $qb->createNamedParameter($params['scope']))
            ->set('u.tenant', $qb->createNamedParameter($params['tenant']))
            ->set('u.token_type', $qb->createNamedParameter($params['token_type']))
            ->set('u.uid', $qb->createNamedParameter($params['uid']))
            ->where($qb->expr()->eq('u.id', $qb->createNamedParameter($params['id'])))
            ->executeStatement();
    }
    /**
     * @return array
     */
    public function get_all_accesstoken(): array
    {
        /**
         * @var IQueryBuilder $qb
         * */
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('u.provider_id', 'p.id', 'p.client_id', 'p.client_secret', 'u.id', 'u.refresh_token', 'p.token_endpoint', 'u.uid', 'u.expires_in', 'u.timestamp')
            ->from('ioidc_userconfig', 'u')
            ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
            ->executeQuery();

        return $rows->fetchAll();
    }
    /**
     * @param String $uid
     * @return array
     *
     */
    public function query_user(String $uid): array
    {
        /**
         * @var IQueryBuilder $qb
         * */
        $qb = $this->db->getQueryBuilder();

        $rows = $qb->select('u.id', 'u.provider_id', 'p.name', 'p.auth_endpoint', 'p.client_id', 'p.client_secret')
            ->from('ioidc_userconfig', 'u')
            ->where(
                $qb->expr()->eq(
                    'u.uid',
                    $qb->createNamedParameter($uid)
                )
            )
            ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
            ->executeQuery();

        return $rows->fetchAll();
    }
    /**
     * @param array $params
     * @return string
     */
    public function get_refresh_token(array $params)
    {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')->from($this::TABLE_NAME)
            ->where(
                $qb->expr()->eq(
                    'id',
                    $qb->createNamedParameter($params['id'])
                )
            );
        $entity = $this->findEntity($query);
        return $entity->getRefreshToken();
    }
    /**
     * @param array $params
     */
    public function delete_user($params)
    {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')->from($this::TABLE_NAME)
            ->where(
                $qb->expr()->eq(
                    'id',
                    $qb->createNamedParameter($params['id'])
                )
            );
        $entity = $this->findEntity($query);
        $this->delete($entity);
    }
}
