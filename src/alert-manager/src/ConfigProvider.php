<?php
declare(strict_types=1);
namespace HyperfAdmin\AlertManager;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'redis' => [
                'alert_manager' => [
                    'host' => env('REDIS_ALERT_MANAGER_HOST', 'localhost'),
                    'auth' => env('REDIS_ALERT_MANAGER_AUTH', null),
                    'port' => (int)env('REDIS_ALERT_MANAGER_PORT', 6379),
                    'db' => (int)env('REDIS_ALERT_MANAGER_DB', 0),
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 10,
                        'connect_timeout' => 10.0,
                        'wait_timeout' => 3.0,
                        'heartbeat' => -1,
                        'max_idle_time' => (float)env('REDIS_ALERT_MANAGER_MAX_IDLE_TIME', 60),
                    ],
                ],
            ],
            'async_queue' => [
                'alert_manager' => [
                    'driver' => \Hyperf\AsyncQueue\Driver\RedisDriver::class,
                    'redis' => [
                        'pool' => 'alert_manager',
                    ],
                    'channel' => 'alert_manager',
                    'timeout' => 2,
                    'retry_seconds' => 5,
                    'handle_timeout' => 10,
                    'processes' => 1,
                    'concurrent' => [
                        'limit' => 5,
                    ],
                ],
            ],
            'commands' => [],
            'dependencies' => [],
            'processes' => [
                AlertQueueConsumer::class,
            ],
            'listeners' => [],
            'publish' => [],
        ];
    }
}
