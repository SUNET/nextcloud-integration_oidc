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
  )
  {
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
    $qb->update($this::TABLE_NAME, 'u')
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
      ->from($this::TABLE_NAME, 'u')
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
      ->from($this::TABLE_NAME, 'u')
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
   * @param int $id
   * @return array
   */
  public function get_refresh_token(int $id): array
  {
    $qb = $this->db->getQueryBuilder();
    $result = $qb->select('u.refresh_token', 'p.revoke_endpoint', 'u.provider_id', 'u.code')->from($this::TABLE_NAME, 'u')
      ->where(
        $qb->expr()->eq(
          'u.id',
          $qb->createNamedParameter($id)
        )
      )
      ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
      ->executeQuery()->fetchAll();
    if (!$result) {
      return array();
    }
    return $result[0];
  }
  /**
   * @param int $id
   */
  public function delete_user(int $id)
  {
    $qb = $this->db->getQueryBuilder();
    $query = $qb->select('*')->from($this::TABLE_NAME)
      ->where(
        $qb->expr()->eq(
          'id',
          $qb->createNamedParameter($id)
        )
      );
    $entity = $this->findEntity($query);
    $this->delete($entity);
  }
}
