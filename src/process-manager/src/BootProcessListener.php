<?php
declare(strict_types=1);
namespace HyperfAdmin\ProcessManager;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ProcessInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Process\Annotation\Process;
use Hyperf\Process\ProcessManager;
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
        $serverConfig = $event->serverConfig;
        $serverProcesses = $serverConfig['processes'] ?? [];
        $processes = $this->config->get('processes', []);
        $annotationProcesses = $this->getAnnotationProcesses();
        $app_processes = array_merge($processes, array_keys($annotationProcesses));
        $manager_config = $this->config->get('process_manager.process', []);
        $app_processes = process_list_filter($app_processes, $manager_config);
        // Retrieve the processes have been registered.
        $processes = array_merge($serverProcesses, $app_processes, ProcessManager::all());
        foreach ($processes as $process) {
            if (is_string($process)) {
                $instance = $this->container->get($process);
                if (isset($annotationProcesses[$process])) {
                    foreach ($annotationProcesses[$process] as $property => $value) {
                        if (property_exists($instance, $property) && !is_null($value)) {
                            $instance->{$property} = $value;
                        }
                    }
                }
            } else {
                $instance = $process;
            }
            if ($instance instanceof ProcessInterface) {
                $instance->isEnable() && $instance->bind($server);
            }
        }
    }

    private function getAnnotationProcesses()
    {
        return AnnotationCollector::getClassByAnnotation(Process::class);
    }
}
