<?php
declare(strict_types=1);
namespace HyperfAdmin\Admin;

use HyperfAdmin\Admin\Install\InstallCommand;
use HyperfAdmin\Admin\Install\UpdateCommand;
use HyperfAdmin\Admin\Middleware\AuthMiddleware;
use HyperfAdmin\Admin\Middleware\PermissionMiddleware;
use HyperfAdmin\BaseUtils\Middleware\CorsMiddleware;
use HyperfAdmin\BaseUtils\Middleware\HttpLogMiddleware;

class ConfigProvider
{
    public function __invoke(): array
    {
        $config = require_once __DIR__ . '/config/config.php';

        return array_overlay($config, [
            'commands' => [
                InstallCommand::class,
                UpdateCommand::class,
            ],
            'dependencies' => [],
            'listeners' => [],
            'publish' => [],
            'middlewares' => [
                'http' => [
                    CorsMiddleware::class,
                    AuthMiddleware::class,
                    PermissionMiddleware::class,
                    HttpLogMiddleware::class
                ]
            ]
        ]);
    }
}
