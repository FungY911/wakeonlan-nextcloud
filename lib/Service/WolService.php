<?php
declare(strict_types=1);

namespace OCA\Wol\Service;

class WolService {
  public function wake(string $mac, string $broadcast, int $port = 9): void {
    $mac = preg_replace('/[^0-9A-Fa-f]/', '', $mac);
    if (strlen($mac) !== 12) {
      throw new \InvalidArgumentException('Bad MAC for WOL');
    }
    $hwaddr = pack('H12', $mac);
    $packet = str_repeat(chr(0xFF), 6) . str_repeat($hwaddr, 16);

    $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($sock === false) {
      throw new \RuntimeException('socket_create failed');
    }
    @socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    $ok = @socket_sendto($sock, $packet, strlen($packet), 0, $broadcast, $port);
    @socket_close($sock);
    if ($ok === false) {
      throw new \RuntimeException('socket_sendto failed');
    }
  }
}
