<?php
declare(strict_types=1);
namespace HyperfAdmin\CronCenter;

use Hyperf\Crontab\Listener\CrontabRegisterListener as HyperfCrontabRegisterListener;
use Hyperf\Crontab\Strategy\Executor as HyperfCrontabExecutor;
use HyperfAdmin\CronCenter\CrontabRegisterListener as CronCenterCrontabRegisterListener;
use HyperfAdmin\CronCenter\Executor as CronCenterExecutor;
use HyperfAdmin\CronCenter\Install\InstallCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'databases' => [
                'cron_center' => db_complete([
                    'host' => env('CRON_CENTER_DB_HOST', env('HYPERF_ADMIN_DB_HOST')),
                    'database' => env('CRON_CENTER_DB_NAME', env('HYPERF_ADMIN_DB_NAME')),
                    'username' => env('CRON_CENTER_DB_USER', env('HYPERF_ADMIN_DB_USER')),
                    'password' => env('CRON_CENTER_DB_PWD', env('HYPERF_ADMIN_DB_PWD')),
                    'prefix' => env('CRON_CENTER_DB_PREFIX', env('HYPERF_ADMIN_DB_PREFIX')),

                ]),
            ],
            'commands' => [
                InstallCommand::class,
            ],
            'dependencies' => [
                HyperfCrontabRegisterListener::class => CronCenterCrontabRegisterListener::class,
                HyperfCrontabExecutor::class => CronCenterExecutor::class,
            ],
            'processes' => [
                CrontabDispatcherProcess::class,
            ],
            'listeners' => [],
            'init_routes' => [
                __DIR__ . '/config/routes.php',
            ],
        ];
    }
}
