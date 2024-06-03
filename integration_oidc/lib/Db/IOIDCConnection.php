<?php

namespace OCA\IOIDC\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class IOIDCConnection
{

  private $db;

  public function __construct(IDBConnection $db)
  {
    $this->db = $db;
  }
  public function query()
  {
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();

    $rows = $qb->select('id', 'name', 'token_endpoint', 'client_id', 'auth_endpoint')
      ->from('ioidc_providers')->executeQuery();

    return $rows->fetchAll();
  }
  public function query_state(String $uid, String $state)
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

    $rows = $qb->select('s.id', 's.provider_id', 's.state', 'p.name', 'p.token_endpoint', 'p.client_id', 'p.client_secret')
      ->from('ioidc_stateconfig', 's')
      ->where($expr)
      ->andWhere($and_expr)
      ->innerJoin('s', 'ioidc_providers', 'p', 's.provider_id = p.id')
      ->executeQuery();

    return $rows->fetchOne();
  }
  public function query_user(String $uid)
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
  public function register(array $params)
  {
    $auth_endpoint = $params['auth_endpoint'];
    $client_id = $params['client_id'];
    $client_secret = $params['client_secret'];
    $grant_type = $params['grant_type'];
    $name = $params['name'];
    $scope = $params['scope'];
    $token_endpoint = $params['token_endpoint'];
    $user_endpoint = $params['user_endpoint'];
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();

    $qb->insert('ioidc_providers')->values(array(
      'auth_endpoint' => $qb->createNamedParameter($auth_endpoint),
      'client_id' => $qb->createNamedParameter($client_id),
      'client_secret' => $qb->createNamedParameter($client_secret),
      'grant_type' => $qb->createNamedParameter($grant_type),
      'name' => $qb->createNamedParameter($name),
      'scope' => $qb->createNamedParameter($scope),
      'token_endpoint' => $qb->createNamedParameter($token_endpoint),
      'user_endpoint' => $qb->createNamedParameter($user_endpoint)
    ));
    $qb->executeStatement();
    $id = $qb->getLastInsertId();
    return $id;
  }
  public function register_state(array $params)
  {
    $uid = $params['uid'];
    $provider_id = $params['id'];
    $state = $params['state'];
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();

    $qb->insert('ioidc_stateconfig')->values(array(
      'uid' => $qb->createNamedParameter($uid),
      'provider_id' => $qb->createNamedParameter($provider_id),
      'state' => $qb->createNamedParameter($state),
    ));
    $qb->executeStatement();
    $id = $qb->getLastInsertId();
    return $id;
  }
  public function register_user(array $params)
  {
    $uid = $params['uid'];
    $provider_id = $params['provider_id'];
    $access_token = $params['access_token'];
    $refresh_token = $params['refresh_token'];
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();

    $qb->insert('ioidc_userconfig')->values(array(
      'uid' => $qb->createNamedParameter($uid),
      'provider_id' => $qb->createNamedParameter($provider_id),
      'access_token' => $qb->createNamedParameter($access_token),
      'refresh_token' => $qb->createNamedParameter($refresh_token),
    ));
    $qb->executeStatement();
    $id = $qb->getLastInsertId();
    return $id;
  }

  public function remove(int $id)
  {
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();

    $qb->delete('ioidc_providers')->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
    $qb->delete('ioidc_userconfig')->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($id)));

    $qb->executeStatement();

    return;
  }
  public function remove_user(array $params)
  {
    $id = $params['id'];
    $uid = $params['uid'];
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();
    $expr = $qb->expr()->eq('id', $qb->createNamedParameter($id));
    $and_expr = $qb->expr()->eq('uid', $qb->createNamedParameter($uid));

    $qb->delete('ioidc_userconfig')
      ->where($expr)->andWhere($and_expr);

    $qb->executeStatement();

    return;
  }
}
