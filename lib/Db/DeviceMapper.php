<?php
declare(strict_types=1);

namespace OCA\Wol\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class DeviceMapper extends QBMapper {
  public function __construct(IDBConnection $db) {
    // Table name without prefix; NC applies oc_ automatically
    parent::__construct($db, 'wol_devices', Device::class);
  }

  /** @return Device[] */
  public function findByUser(string $userId): array {
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')->from('wol_devices')
       ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
       ->orderBy('id', 'DESC');
    return $this->findEntities($qb);
  }

  public function insertForUser(string $userId, string $name, string $mac, string $host, string $broadcast, int $port): Device {
    $d = new Device();
    $d->setUserId($userId);
    $d->setName($name);
    $d->setMac($mac);
    $d->setHost($host !== '' ? $host : null);
    $d->setBroadcast($broadcast);
    $d->setPort($port);
    $d->setCreatedAt(time());
    /** @var Device */
    return $this->insert($d);
  }

  public function updateForUser(string $uid, int $id, string $name, string $mac, string $host, string $broadcast, int $port): Device {
    $dev = $this->getForUserById($uid, $id);
    if (!$dev) throw new \RuntimeException('Not found');

    $dev->setName($name);
    $dev->setMac($mac);
    $dev->setHost($host);
    $dev->setBroadcast($broadcast);
    $dev->setPort($port);

    return $this->mapper->update($dev);
  }

  public function deleteForUser(string $userId, int $id): int {
    $qb = $this->db->getQueryBuilder();
    $qb->delete('wol_devices')
       ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
       ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
    return (int)$qb->executeStatement();
  }
}
