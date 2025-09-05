<?php
declare(strict_types=1);

namespace OCA\Wol\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCA\Wol\Db\DeviceMapper;

// ✅ import the attribute classes
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class PageController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $devices,
        private IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]   // ← new style
    #[NoCSRFRequired]    // ← new style
    public function index(): TemplateResponse {
        $user = $this->userSession->getUser();
        $rows = [];
        if ($user) {
            foreach ($this->devices->findByUser($user->getUID()) as $d) {
                $rows[] = [
                    'id'        => $d->getId(),
                    'name'      => $d->getName(),
                    'mac'       => $d->getMac(),
                    'host'       => $d->getHost(),
                    'broadcast' => $d->getBroadcast(),
                    'port'      => $d->getPort(),
                ];
            }
        }
        return new TemplateResponse('wol', 'main', ['devices' => $rows]);
    }
}
