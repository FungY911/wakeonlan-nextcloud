<?php
declare(strict_types=1);

namespace OCA\Wol\Controller;

use OCA\Wol\AppInfo\Application;
use OCA\Wol\Db\DeviceMapper;
use OCA\Wol\Service\WolService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

// Attributes (NC 29+)
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\CSRFRequired;

class WolController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private WolService $wol,
        private DeviceMapper $devices,
        private IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
    }

    private function err(string $message, int $status = 400, ?string $field = null): JSONResponse {
        $payload = ['error' => $message];
        if ($field !== null) $payload['field'] = $field;
        return new JSONResponse($payload, $status);
    }

    private function isValidHost(string $host): bool {
        if ($host === '') return false;
        // IPv4
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return true;
        // simple hostname (letters/digits/dash/dot), no spaces
        return (bool)preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$/i', $host);
    }

    #[NoAdminRequired]
    #[CSRFRequired]
    public function status(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) return $this->err('Unauthorized', 401);

        $rows = $this->devices->findByUser($user->getUID());
        $map = [];
        foreach ($rows as $d) {
            $id = (int)trim((String)$d->getId());
            $host = trim($d->getHost() ?? '');
            if ($host !== '') {
                $map[$id] = $host;
            }
        }

        $onlineSet = $this->probeMany(array_values($map), 600);
        $out = [];
        foreach ($rows as $d) {
            $id = (int)$d->getId();
            $host = $map[$id] ?? '';
            $online = ($host !== '' && !empty($onlineSet[$host]));
            $out[] = ['id' => $id, 'host' => $host, 'online' => $online];
        }
        return new JSONResponse(['devices' => $out, 'ts' => time()]);
    }

    /**
     * Probe many hosts quickly.
     * 1) Primary: TCP connect on [445,3389,22]
     * 2) Secondary: fping -a (only for still-offline hosts)
     * @return array<string,bool> map host => online?
     */
    private function probeMany(array $hosts, int $timeoutMs = 500): array {
        $hosts = array_values(array_unique(array_filter(array_map('trim', $hosts))));
        $result = [];
        foreach ($hosts as $h) $result[$h] = false;
        if (!$hosts) return $result;

        if (function_exists('exec')) {
            $lines = [];
            $exit  = 1;
            @exec('command -v fping 2>/dev/null', $lines, $exit);

            $fpingPath = ($exit === 0 && isset($lines[0]) && is_string($lines[0]))
                ? trim($lines[0])
                : '';

            // Optional: also ensure it's executable
            if ($fpingPath !== '' && @is_executable($fpingPath)) {
                $cmd = sprintf(
                    '%s -4 -a -t %d -r 0 %s 2>/dev/null',
                    escapeshellcmd($fpingPath),
                    max(1, (int)$timeoutMs),
                    implode(' ', array_map('escapeshellarg', $hosts))
                );

                $out  = [];
                $code = 0;
                @exec($cmd, $out, $code);

                if (!empty($out) || $code === 0) {
                    foreach ($out as $line) {
                        if (!is_string($line)) { continue; }
                        $ip = trim($line);
                        if ($ip !== '' && isset($result[$ip])) {
                            $result[$ip] = true;
                        }
                    }
                }
            }
        }


        $remaining = [];
        foreach ($result as $h => $online) {
            if (!$online) $remaining[] = $h;
        }
        if (!$remaining) return $result;

        foreach ($remaining as $h) {
            if ($this->isOnlineTcp($h, [445, 3389, 22], $timeoutMs)) {
                $result[$h] = true;
            }
        }

        return $result;
    }

    private function isOnlineTcp(string $host, array $ports = [445, 3389, 22], int $timeoutMs = 500): bool {
        $host = trim($host);
        if ($host === '') return false;

        $timeout = max(0.1, $timeoutMs / 1000.0); // seconds (float)
        foreach ($ports as $p) {
            $p = (int)$p;
            if ($p <= 0 || $p > 65535) continue;
            $errno = 0; $errstr = '';
            $conn = @fsockopen($host, $p, $errno, $errstr, $timeout);
            if (is_resource($conn)) {
                @fclose($conn);
                return true; // any open port => online
            }
        }
        return false;
    }


    #[NoAdminRequired]
    #[CSRFRequired]
    public function add(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) return $this->err('Unauthorized', 401);

        $name      = trim((string)$this->request->getParam('name', ''));
        $mac       = strtoupper(trim((string)$this->request->getParam('mac', '')));
        $host      = trim((string)$this->request->getParam('host', ''));       // NEW
        $broadcast = trim((string)$this->request->getParam('broadcast', ''));
        $portRaw = $this->request->getParam('port', null);

        if ($name === '') return $this->err('Name required', 422, 'name');
        if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac)) return $this->err('Invalid MAC', 422, 'mac');
        if (!$this->isValidHost($host)) return $this->err('Invalid host/IP', 422, 'host');
        if ($broadcast !== '' && !filter_var($broadcast, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->err('Invalid broadcast IP', 422, 'broadcast');
        }

        if (is_array($portRaw)) { // guard against array injection
            return $this->err('Invalid port', 422, 'port');
        }
        $portRaw = is_string($portRaw) ? trim($portRaw) : $portRaw;

        $port = filter_var($portRaw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($port === false) {
            return $this->err('Invalid port', 422, 'port'); // not an int or out of range
        }

        try {
            $dev = $this->devices->insertForUser($user->getUID(), $name, $mac, $host, $broadcast, $port);
            return new JSONResponse([
                'ok' => true,
                'device' => [
                    'id' => (int)$dev->getId(),
                    'name' => $dev->getName(),
                    'mac' => $dev->getMac(),
                    'host' => $dev->getHost(),
                    'broadcast' => $dev->getBroadcast(),
                    'port' => (int)$dev->getPort(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->err('Server error while saving device.', 500);
        }
    }

    #[NoAdminRequired]
    #[CSRFRequired]
    public function wake(int $id): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) return $this->err('Unauthorized', 401);

            $list = $this->devices->findByUser($user->getUID());
            $target = null;
            foreach ($list as $d) {
                if ((int)$d->getId() === (int)$id) { $target = $d; break; }
            }
            if (!$target) return $this->err('Unknown device', 404);

            $this->wol->wake($target->getMac(), $target->getBroadcast(), (int)$target->getPort());

            return new JSONResponse(['ok' => true, 'id' => (int)$id]);
        } catch (\Throwable $e) {
            return $this->err('Server error while waking device', 500);
        }
    }

    #[NoAdminRequired]
    #[CSRFRequired]
    public function delete(int $id): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) return $this->err('Unauthorized', 401);

            $deleted = $this->devices->deleteForUser($user->getUID(), (int)$id);
            if ($deleted === 0) return $this->err('Unknown device', 404);

            return new JSONResponse(['ok' => true, 'id' => (int)$id]);
        } catch (\Throwable $e) {
            return $this->err('Server error while deleting device', 500);
        }
    }
}

