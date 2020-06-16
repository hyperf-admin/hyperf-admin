<?php
declare(strict_types=1);
namespace HyperfAdmin\ProcessManager;

use Hyperf\Amqp\Annotation\Consumer as ConsumerAnnotation;
use Hyperf\Amqp\Consumer;
use Hyperf\Amqp\Message\ConsumerMessageInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Psr\Container\ContainerInterface;

class AmqpConsumerManager
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function run()
    {
        $classes = AnnotationCollector::getClassByAnnotation(ConsumerAnnotation::class);
        $consumer_names = process_list_filter(array_keys($classes), config('process_manager.amqp_consumer', []));
        /**
         * @var string
         * @var ConsumerAnnotation $annotation
         */
        foreach($classes as $class => $annotation) {
            if(!in_array($class, $consumer_names)) {
                continue;
            }
            $instance = make($class);
            if(!$instance instanceof ConsumerMessageInterface) {
                continue;
            }
            $annotation->exchange && $instance->setExchange($annotation->exchange);
            $annotation->routingKey && $instance->setRoutingKey($annotation->routingKey);
            $annotation->queue && $instance->setQueue($annotation->queue);
            !is_null($annotation->enable) && $instance->setEnable($annotation->enable);
            property_exists($instance, 'container') && $instance->container = $this->container;
            $nums = $annotation->nums;
            $process = $this->createProcess($instance);
            $process->nums = (int)$nums;
            $process->name = $annotation->name . '-' . $instance->getQueue();
            ProcessManager::register($process);
        }
    }

    private function createProcess(ConsumerMessageInterface $consumerMessage): AbstractProcess
    {
        return new class($this->container, $consumerMessage) extends AbstractProcess {
            /**
             * @var \Hyperf\Amqp\Consumer
             */
            private $consumer;

            /**
             * @var ConsumerMessageInterface
             */
            private $consumerMessage;

            public function __construct(ContainerInterface $container, ConsumerMessageInterface $consumerMessage)
            {
                parent::__construct($container);
                $this->consumer = $container->get(Consumer::class);
                $this->consumerMessage = $consumerMessage;
            }

            public function handle(): void
            {
                $this->consumer->consume($this->consumerMessage);
            }

            public function getConsumerMessage(): ConsumerMessageInterface
            {
                return $this->consumerMessage;
            }

            public function isEnable(): bool
            {
                return $this->consumerMessage->isEnable();
            }
        };
    }
}
