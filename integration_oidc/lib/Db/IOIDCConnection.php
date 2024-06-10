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

    $rows = $qb->select('id', 'name', 'token_endpoint', 'client_id', 'auth_endpoint', 'scope', 'user_endpoint')
      ->from('ioidc_providers')->executeQuery();

    return $rows->fetchAll();
  }
  public function get_refresh_token(array $params)
  {
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();
    $expr = $qb->expr()->eq(
      'u.id',
      $qb->createNamedParameter($params['id'])
    );

    $rows = $qb->select('u.id', 'u.refresh_token', 'u.provider_id', 'p.revoke_endpoint')
      ->from('ioidc_userconfig', 'u')
      ->where($expr)
      ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
      ->executeQuery();

    return $rows->fetchAll()[0];
  }
  public function get_all_accesstoken()
  {
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();
    $rows = $qb->select('u.provider_id', 'p.id', 'p.client_id','p.client_secret', 'u.id', 'u.refresh_token', 'p.token_endpoint', 'u.uid', 'u.expires_in', 'u.timestamp')
      ->from('ioidc_userconfig', 'u')
      ->innerJoin('u', 'ioidc_providers', 'p', 'u.provider_id = p.id')
      ->executeQuery();

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

    $rows = $qb->select('s.id', 's.provider_id', 's.state', 's.uid', 'p.name', 'p.token_endpoint', 'p.client_id', 'p.client_secret')
      ->from('ioidc_stateconfig', 's')
      ->where($expr)
      ->andWhere($and_expr)
      ->innerJoin('s', 'ioidc_providers', 'p', 's.provider_id = p.id')
      ->executeQuery();

    return $rows->fetchAll()[0];
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
  public function refresh_token(array $params)
  {

    // 'access_token' => $access_token,
    // 'expires_in' => $expires_in,
    // 'id' => $id,
    // 'scope' => $scope,
    // 'token_type' => $token_type,
    // 'uid' => $uid
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();
    $qb->update('ioidc_userconfig', 'u')
      ->set('u.access_token', $qb->createNamedParameter($params['access_token']))
      ->set('u.expires_in', $qb->createNamedParameter($params['expires_in']))
      ->set('u.timestamp', $qb->createNamedParameter(time()))
      ->set('u.scope', $qb->createNamedParameter($params['scope']))
      ->set('u.token_type', $qb->createNamedParameter($params['token_type']))
      ->set('u.uid', $qb->createNamedParameter($params['uid']))
      ->where($qb->expr()->eq('u.id', $qb->createNamedParameter($params['id'])))
      ->executeStatement();
  }
  public function register(array $params)
  {
    $auth_endpoint = $params['auth_endpoint'];
    $client_id = $params['client_id'];
    $client_secret = $params['client_secret'];
    $name = $params['name'];
    $scope = $params['scope'];
    $revoke_endpoint = $params['token_endpoint'];
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
      'name' => $qb->createNamedParameter($name),
      'scope' => $qb->createNamedParameter($scope),
      'revoke_endpoint' => $qb->createNamedParameter($revoke_endpoint),
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
    $access_token = $params['access_token'];
    $expires_in = $params['expires_in'];
    $provider_id = $params['provider_id'];
    $refresh_token = $params['refresh_token'];
    $scope = $params['scope'];
    $timestamp = time();
    $token_type = $params['token_type'];
    $uid = $params['uid'];
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();

    $qb->insert('ioidc_userconfig')->values(array(
      'access_token' => $qb->createNamedParameter($access_token),
      'expires_in' => $qb->createNamedParameter($expires_in),
      'provider_id' => $qb->createNamedParameter($provider_id),
      'refresh_token' => $qb->createNamedParameter($refresh_token),
      'scope' => $qb->createNamedParameter($scope),
      'timestamp' => $qb->createNamedParameter($timestamp),
      'token_type' => $qb->createNamedParameter($token_type),
      'uid' => $qb->createNamedParameter($uid),
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
