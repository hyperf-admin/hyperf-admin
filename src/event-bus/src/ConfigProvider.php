<?php
declare(strict_types=1);
namespace HyperfAdmin\EventBus;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'commands' => [],
            'dependencies' => [],
            'processes' => [],
            'listeners' => [
                BootProcessListener::class,
                OnPipeMessageListener::class,
            ],
            'publish' => [
                [
                    'id' => 'event-bus',
                    'description' => 'The config for event-bus.',
                    'source' => __DIR__ . '/../publish/event-bus.php',
                    'destination' => BASE_PATH . '/config/autoload/event-bus.php',
                ],
            ],
        ];
    }
}
