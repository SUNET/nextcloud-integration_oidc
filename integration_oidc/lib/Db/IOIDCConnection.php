<?php
// db/authordao.php

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

    $rows = $qb->select('id','name','token_endpoint')
      ->from('ioidc_providers')->executeQuery();

    return $rows->fetchAll();
  }
  public function register(String $name, String $client_id, String $client_secret, String $token_endpoint)
  {
    /**
     * @var IQueryBuilder $qb
     * */
    $qb = $this->db->getQueryBuilder();

    $qb->insert('ioidc_providers')->values(array(
      'name' => $qb->createNamedParameter($name),
      'client_id' => $qb->createNamedParameter($client_id),
      'client_secret' => $qb->createNamedParameter($client_secret),
      'token_endpoint' => $qb->createNamedParameter($token_endpoint)
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

    $qb->executeStatement();

    return;
  }
}
