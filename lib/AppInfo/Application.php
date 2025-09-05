<?php
declare(strict_types=1);

namespace OCA\Wol\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\IAppContainer;
use OCP\IDBConnection;

use OCA\Wol\Db\DeviceMapper;
use OCA\Wol\Service\WolService;
use OCA\Wol\Controller\WolController;
use OCA\Wol\Controller\PageController;

class Application extends App implements IBootstrap {
    public const APP_ID = 'wol';

    public function __construct(array $params = []) {
        parent::__construct(self::APP_ID, $params);

        /** @var IAppContainer $c */
        $c = $this->getContainer();

        // Services
        $c->registerService(DeviceMapper::class, fn(IAppContainer $c)
            => new DeviceMapper($c->query(IDBConnection::class)));
        $c->registerService(WolService::class, fn() => new WolService());

        // Controllers (5 args, no logger)
        $c->registerService(WolController::class, fn(IAppContainer $c)
            => new WolController(
                self::APP_ID,
                $c->query('OCP\\IRequest'),
                $c->query(WolService::class),
                $c->query(DeviceMapper::class),
                $c->query('OCP\\IUserSession')
            ));

        $c->registerService(PageController::class, fn(IAppContainer $c)
            => new PageController(
                self::APP_ID,
                $c->query('OCP\\IRequest'),
                $c->query(DeviceMapper::class),
                $c->query('OCP\\IUserSession')
            ));
    }

    public function register(IRegistrationContext $context): void {}
    public function boot(IBootContext $context): void {}
}
