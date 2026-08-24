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
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<IOIDCUser>
 */
class IOIDCUserMapper extends QBMapper
{
    public const TABLE_NAME = 'ioidc_userconfig';
    private LoggerInterface $logger;

    public function __construct(
        IDBConnection $db,
        LoggerInterface $logger
    ) {
        parent::__construct($db, self::TABLE_NAME);
        $this->logger = $logger;
    }
    public function get_user(array $params): IOIDCUser
    {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')
          ->from($this::TABLE_NAME)
          ->where(
              $qb->expr()->eq(
                  'uid',
                  $qb->createNamedParameter(
                      $params['uid']
                  )
              )
          )
          ->andWhere(
              $qb->expr()->eq(
                  'provider_id',
                  $qb->createNamedParameter(
                      $params['provider_id']
                  )
              )
          );
        return $this->findEntity($query);
    }
    /**
     * @param array $params
     * @return int
     */
    public function register_user(array $params): int
    {
        $entity = new IOIDCUser();
        $entity = $entity->setParams($params);
        $entity->setRevision(1);
        $connectionKey = $this->connectionKey((string)$params['uid'], (int)$params['providerId']);
        $entity->setConnectionKey($connectionKey);

        $existing = $this->findActiveConnection($connectionKey);
        if ($existing !== []) {
            $entity = $existing[0]->setParams($params);
            $entity->setRevision((int)$entity->getRevision() + 1);
            $this->update($entity);
            return $entity->getId();
        }

        try {
            $entity = $this->insert($entity);
        } catch (\Throwable $e) {
            $existing = $this->findActiveConnection($connectionKey);
            if ($existing === []) {
                throw $e;
            }
            $entity = $existing[0]->setParams($params);
            $entity->setRevision((int)$entity->getRevision() + 1);
            $this->update($entity);
        }
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
        $updated = $qb->update($this::TABLE_NAME)
          ->set('access_token', $qb->createNamedParameter($params['access_token']))
          ->set('expires_in', $qb->createNamedParameter($params['expires_in'], IQueryBuilder::PARAM_INT))
          ->set('timestamp', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
          ->set('scope', $qb->createNamedParameter($params['scope']))
          ->set('token_type', $qb->createNamedParameter($params['token_type']))
          ->set('refresh_token', $qb->createNamedParameter($params['refresh_token']))
          ->set('revision', $qb->createNamedParameter($params['original_revision'] + 1, IQueryBuilder::PARAM_INT))
          ->where($qb->expr()->eq('id', $qb->createNamedParameter($params['id'], IQueryBuilder::PARAM_INT)))
          ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($params['uid'])))
          ->andWhere($qb->expr()->eq('provider_version', $qb->createNamedParameter($params['provider_version'], IQueryBuilder::PARAM_INT)))
          ->andWhere($qb->expr()->eq('revision', $qb->createNamedParameter($params['original_revision'], IQueryBuilder::PARAM_INT)))
          ->executeStatement();
        if ($updated !== 1) {
            throw new \RuntimeException('OIDC connection changed while its token was being refreshed');
        }
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
        $rows = $qb->select('u.provider_id', 'p.client_id', 'p.client_secret', 'u.id', 'u.refresh_token', 'u.scope', 'p.token_endpoint', 'p.token_endpoint_auth_method', 'u.uid', 'u.expires_in', 'u.timestamp', 'u.provider_version', 'u.revision')
          ->from($this::TABLE_NAME, 'u')
          ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
          ->where($qb->expr()->eq('u.provider_version', 'p.config_version'))
          ->andWhere($qb->expr()->isNotNull('u.connection_key'))
          ->executeQuery();

        $result = $rows->fetchAll();
        $rows->closeCursor();

        return $result;
    }
    /**
     * @param string $uid
     * @return array
     *
     */
    public function query_user(string $uid): array
    {
        /**
         * @var IQueryBuilder $qb
         * */
        $qb = $this->db->getQueryBuilder();

        $rows = $qb->select('u.id', 'u.provider_id', 'p.name', 'u.provider_version', 'p.config_version', 'u.connection_key')
          ->from($this::TABLE_NAME, 'u')
          ->where(
              $qb->expr()->eq(
                  'u.uid',
                  $qb->createNamedParameter($uid)
              )
          )
          ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
          ->executeQuery();

        $result = $rows->fetchAll();
        $rows->closeCursor();
        foreach ($result as &$row) {
            $row['requires_reauthorization'] = (int)$row['provider_version'] !== (int)$row['config_version'];
            $row['archived'] = !is_string($row['connection_key']) || $row['connection_key'] === '';
            unset($row['provider_version'], $row['config_version'], $row['connection_key']);
        }
        unset($row);

        return $result;
    }
    /**
     * @param int $id
     * @return array
     */
    public function get_refresh_token(int $id, string $uid): array
    {
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('u.refresh_token', 'p.revoke_endpoint', 'p.client_id', 'p.client_secret', 'p.token_endpoint_auth_method', 'u.provider_id', 'u.provider_version', 'p.config_version')->from($this::TABLE_NAME, 'u')
          ->where(
              $qb->expr()->eq(
                  'u.id',
                  $qb->createNamedParameter($id)
              )
          )
          ->andWhere($qb->expr()->eq('u.uid', $qb->createNamedParameter($uid)))
          ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
          ->executeQuery();
        $result = $rows->fetchAll();
        $rows->closeCursor();
        if (!$result) {
            return array();
        }
        return $result[0];
    }
    /**
     * @param int $id
     */
    public function delete_user(int $id, string $uid): void
    {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')->from($this::TABLE_NAME)
          ->where(
              $qb->expr()->eq(
                  'id',
                  $qb->createNamedParameter($id)
              )
          );
        $query->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));
        $entity = $this->findEntity($query);
        $this->delete($entity);
    }

    public function has_provider_connections(int $providerId): bool
    {
        $qb = $this->db->getQueryBuilder();
        $result = $qb->select('id')
          ->from($this::TABLE_NAME)
          ->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId, IQueryBuilder::PARAM_INT)))
          ->setMaxResults(1)
          ->executeQuery();
        $hasConnections = $result->fetchOne() !== false;
        $result->closeCursor();

        return $hasConnections;
    }

    /** @return IOIDCUser[] */
    private function findActiveConnection(string $connectionKey): array
    {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')
          ->from($this::TABLE_NAME)
          ->where($qb->expr()->eq('connection_key', $qb->createNamedParameter($connectionKey)));

        return $this->findEntities($query);
    }

    private function connectionKey(string $uid, int $providerId): string
    {
        return hash('sha256', $uid . "\0" . $providerId);
    }
}
