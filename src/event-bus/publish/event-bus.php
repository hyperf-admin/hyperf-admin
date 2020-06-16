<?php

declare(strict_types=1);

use Hyperf\Amqp\Message\Type;

// callback 支持的几种类型
// 'callback' => [Foo::class, 'handle'],
// 'callback' => 'App\Service\Foo@handle1',
// 'callback' => 'App\Service\Foo::handle',
// 'callback' => 'https://suggest.taobao.com/sug?code=utf-8&q=%E5%87%89%E9%9E%8B&_ksTS=1590645171272_1598&k=1&area=c2c&bucketid=20',

return [
    [
        'type' => 'amqp',
        'deliver_type' => 'single', // 非必须 radio 广播|single 单独发送 default single
        'options' => [
            'poolName' => 'default',
            'exchange' => 'exchange_name',
            'routingKey' => 'routingKey',
            'queue' => 'queue_name',
            'nums' => 1,
            'type' => Type::DIRECT,
        ],
        'callback' => [Foo::class, 'handle'],
    ],
    [
        'type' => 'kafka',
        'options' => [
            'instance' => 'order',
            'group' => 'kafka_group_name',
            'topic' => 'kafka_topic_name',
        ],
        'callback' => 'App\Service\Foo@handle',
    ],
];
