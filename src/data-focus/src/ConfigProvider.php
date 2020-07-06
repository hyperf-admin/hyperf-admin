<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus;

use HyperfAdmin\DataFocus\Install\InstallCommand;
use HyperfAdmin\DataFocus\Util\BootAppConfListener as DataFocusBootAppConfListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'databases' => [
                'data_focus' => db_complete([
                    'host' => env('DATA_FOCUS_DB_HOST', env('HYPERF_ADMIN_DB_HOST')),
                    'database' => env('DATA_FOCUS_DB_NAME', env('HYPERF_ADMIN_DB_NAME')),
                    'username' => env('DATA_FOCUS_DB_USER', env('HYPERF_ADMIN_DB_USER')),
                    'password' => env('DATA_FOCUS_DB_PWD', env('HYPERF_ADMIN_DB_PWD')),
                    'prefix' => env('DATA_FOCUS_DB_PREFIX', env('HYPERF_ADMIN_DB_PREFIX')),

                ]),
            ],
            'commands' => [
                InstallCommand::class,
            ],
            'dependencies' => [],
            'listeners' => [
                DataFocusBootAppConfListener::class => -1,
            ],
            'init_routes' => [
                __DIR__ . '/config/routes.php',
            ],
        ];
    }
}
