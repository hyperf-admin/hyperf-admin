<?php

namespace HyperfAdmin\EventBus;

use Hyperf\Amqp\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\ApplicationContext;

class ProcessFactory
{
    public $serve;

    public $deliver_type;

    public $callback;

    public function make($params, $serve)
    {
        $this->serve = $serve;
        $this->deliver_type = $params['deliver_type'] ?? 'single';
        $this->callback = $params['callback'];
        switch($params['type']) {
            case 'amqp':
                return $this->makeAmqpProcess($params['options']);
                break;
            case 'kafka':
                return $this->makeKafkaProcess($params['options']);
                break;
            default:
                return false;
        }
    }

    public function makeAmqpProcess($options)
    {
        $factory = $this;
        $nums = $options['nums'] ?? 1;
        $message = new class ($options, $factory) extends ConsumerMessage {
            public $factory;

            public function __construct($options, $factory)
            {
                foreach($options as $key => $val) {
                    if(property_exists($this, $key)) {
                        $this->$key = $val;
                    }
                }
                $this->factory = $factory;
            }

            public function consume($data): string
            {
                $this->factory->consumer($data);

                return Result::ACK;
            }
        };

        return new class ($message, $nums) extends AbstractProcess {
            public $name;

            public $consumer;

            public $message;

            public function __construct($message, $nums)
            {
                $container = ApplicationContext::getContainer();
                $this->consumer = $container->get(Consumer::class);
                $this->name = 'event_bus:amqp_' . $message->getQueue();
                $this->message = $message;
                $this->nums = $nums;
                parent::__construct($container);
            }

            public function handle(): void
            {
                $this->consumer->consume($this->message);
            }

            public function isEnable($server): bool
            {
                return $this->message->isEnable();
            }
        };
    }

    public function makeKafkaProcess($options)
    {
        $factory = $this;

        return new class ($options, $factory) extends AbstractProcess {
            public $name;

            public $topic;

            public $group;

            public $instance;

            public $factory;

            public function __construct($options, $factory)
            {
                $container = ApplicationContext::getContainer();
                $this->instance = $options['instance'];
                $this->topic = $options['topic'];
                $this->group = $options['group'];
                $this->name = 'event_bus:kafka_' . $this->group . '_' . $this->topic;
                $this->factory = $factory;
                $this->nums = $options['nums'] ?? 1;
                parent::__construct($container);
            }

            public function handle(): void
            {
                $that = $this;
                Kafka::consumer($this->topic, $this->group, function ($data) use ($that) {
                    $data = (array)my_json_decode($data);

                    $that->factory->consumer($data);
                }, $this->instance);
            }

            public function isEnable($server): bool
            {
                return true;
            }
        };
    }

    public function consumer($data)
    {
        $pipe_message = new PipeMessage([
            'callback' => $this->callback,
            'arg' => $data,
        ]);

        $workerCount = $this->serve->setting['worker_num'] + $this->serve->setting['task_worker_num'] - 1;
        $sendWorkerIds = [];
        if($this->deliver_type == 'radio') {
            $sendWorkerIds = range(0, $workerCount);
        }
        if($this->deliver_type == 'single') {
            $sendWorkerIds = (array)mt_rand(0, $workerCount);
        }
        foreach($sendWorkerIds as $workerId) {
            $this->serve->sendMessage($pipe_message, $workerId);
        }
    }
}
