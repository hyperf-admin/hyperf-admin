<?php

declare(strict_types=1);
namespace HyperfAdmin\EventBus;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ProcessInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Psr\Container\ContainerInterface;

class BootProcessListener implements ListenerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event)
    {
        /** @var BeforeMainServerStart $event */
        $server = $event->server;

        $config = $this->config->get('event-bus', []);
        foreach($config as $params) {
            $enable = $params['enable'] ?? true;
            if (!$enable) {
                continue;
            }
            $instance = (new ProcessFactory())->make($params, $server);
            if($instance instanceof ProcessInterface) {
                $instance->isEnable() && $instance->bind($server);
            }
        }
    }
}
