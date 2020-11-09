<?php
namespace HyperfAdmin\ConfigCenter;

use HyperfAdmin\ConfigCenter\Install\InstallCommand;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'databases' => [
                'config_center' => db_complete([
                    'host' => env('CONFIG_CENTER_DB_HOST', env('HYPERF_ADMIN_DB_HOST')),
                    'database' => env('CONFIG_CENTER_DB_NAME', env('HYPERF_ADMIN_DB_NAME')),
                    'username' => env('CONFIG_CENTER_DB_USER', env('HYPERF_ADMIN_DB_USER')),
                    'password' => env('CONFIG_CENTER_DB_PWD', env('HYPERF_ADMIN_DB_PWD')),
                    'prefix' => env('CONFIG_CENTER_DB_PREFIX', env('HYPERF_ADMIN_DB_PREFIX')),
                    'port' => env('CONFIG_CENTER_DB_PORT', env('HYPERF_ADMIN_DB_PORT')),
                ]),
            ],
            'commands' => [
                InstallCommand::class,
            ],
            'listeners' => [
                BootProcessListener::class,
            ],
            'init_routes' => [
                __DIR__ . '/routes.php',
            ],
        ];
    }
}
