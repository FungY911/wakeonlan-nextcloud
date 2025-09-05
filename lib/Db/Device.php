<?php
declare(strict_types=1);

namespace OCA\Wol\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int|null getId()
 * @method void setId(int $id)
 * @method string getUserId()
 * @method void setUserId(string $uid)
 * @method string getName()
 * @method void setName(string $n)
 * @method string getMac()
 * @method void setMac(string $m)
 * @method ?string getHost()
 * @method void setHost(?string $h)
 * @method string getBroadcast()
 * @method void setBroadcast(string $b)
 * @method int getPort()
 * @method void setPort(int $p)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $t)
 */
class Device extends Entity {
    // DO NOT redeclare $id (Entity already has it)
    protected string  $userId    = '';
    protected string  $name      = '';
    protected string  $mac       = '';
    protected ?string $host      = '';
    protected string  $broadcast = '';
    protected int     $port      = 9;
    protected int     $createdAt = 0;

    public function __construct() {
        $this->addType('id', 'int');
        $this->addType('userId', 'string');
        $this->addType('name', 'string');
        $this->addType('mac', 'string');
        $this->addType('host', 'string');
        $this->addType('broadcast', 'string');
        $this->addType('port', 'int');
        $this->addType('createdAt', 'int');
    }
}
