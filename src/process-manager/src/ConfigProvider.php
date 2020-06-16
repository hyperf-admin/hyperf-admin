<?php
namespace HyperfAdmin\ProcessManager;

use Hyperf\Amqp\ConsumerManager as HyperfAmqpConsumerManager;
use Hyperf\Nsq\ConsumerManager as HyperfNsqConsumerManager;
use Hyperf\Process\Listener\BootProcessListener as HyperfBootProcessListener;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => [
                HyperfBootProcessListener::class => BootProcessListener::class,
                HyperfNsqConsumerManager::class => NsqConsumerManager::class,
                HyperfAmqpConsumerManager::class => AmqpConsumerManager::class,
            ],
            'publish' => [
                [
                    'id' => 'process-manager',
                    'description' => 'The config for process_manager.',
                    'source' => __DIR__ . '/../publish/process_manager.php',
                    'destination' => BASE_PATH . '/config/autoload/process_manager.php',
                ],
            ]
        ];
    }
}
