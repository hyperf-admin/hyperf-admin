<?php

namespace HyperfAdmin\EventBus;

use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;

/**
 * code: https://arnaud.le-blanc.net/php-rdkafka-doc/phpdoc/rdkafka.examples.html
 * aliyun kafka: vpc 使用白名单, 公网使用 sasl
 */
class Kafka
{
    public static function consumer(string $topic, string $group, $handler, string $instance = '')
    {
        $conf = new Conf();

        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (KafkaConsumer $kafka, $err, array $partitions = null) {
            switch($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(null);
                    break;

                default:
                    throw new \Exception($err);
            }
        });

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', $group);

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', config("kafka.$instance"));

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $conf->set('auto.offset.reset', 'smallest');

        // kafka version: 0.10.0
        // %5|1583908574.751|MAXPOLL|rdkafka#consumer-1| [thrd:main]: GroupCoordinator/102: Broker does not support KIP-62 (requires Apache Kafka >= v0.10.1.0): consumer configuration `max.poll.interval.ms` (300000) is effectively limited by `session.timeout.ms` (10000) with this broker version
        // $conf->set('max.poll.interval.ms', '10000');

        $consumer = new KafkaConsumer($conf);

        // Subscribe to topic 'test'
        $consumer->subscribe([$topic]);

        // "Waiting for partition assignment... (make take some time when\n";
        // "quickly re-joining the group after leaving it.)\n";

        while(true) {
            /** @var \RdKafka\Message $message */
            $message = $consumer->consume(120 * 1000);
            switch($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $handler($message->payload);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    // "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    // "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    public static function producer(string $topic, string $msg, string $instance = '')
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', config("kafka.$instance"));

        //If you need to produce exactly once and want to keep the original produce order, uncomment the line below
        //$conf->set('enable.idempotence', 'true');

        $producer = new Producer($conf);

        $topic = $producer->newTopic($topic);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $msg);
        $producer->poll(0);
        $result = $producer->flush(10000);
        if(RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }

        return $result;
    }

    // Local: All broker connections are down Local: Broker transport failure)
    // fooldoc.com/archives/180
    public function getProducer(string $instance = '')
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', config("kafka.$instance"));
        $this->producer = new Producer($conf);
    }

    public function produce(Producer $producer, string $topic, string $msg)
    {
        $topic = $producer->newTopic($topic);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $msg);
        while(($len = $producer->getOutQLen()) > 0) {
            $producer->poll(50);
        }
    }
}
