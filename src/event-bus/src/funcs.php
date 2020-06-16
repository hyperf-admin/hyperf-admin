<?php

use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Producer;
use Hyperf\Utils\ApplicationContext;
use HyperfAdmin\EventBus\Kafka;

function amqp_push($exchange, $routingKey, $data, $properties = [], $instance = 'default')
{
    $message = new class ($exchange, $routingKey, $instance, $data, $properties) extends ProducerMessage {
        public function __construct($exchange, $routingKey, $instance, $data, $properties)
        {
            $this->poolName = $instance;

            $this->exchange = $exchange;

            $this->routingKey = $routingKey;

            $this->payload = $data;

            $this->properties = array_merge($this->getProperties(), $properties);
        }
    };
    $producer = ApplicationContext::getContainer()->get(Producer::class);

    return $producer->produce($message);
}

function kafka_push($topic, $message, $instance)
{
    if(is_array($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
    }

    return Kafka::producer($topic, (string)$message, $instance);
}
