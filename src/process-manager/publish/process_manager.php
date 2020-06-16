<?php

/**
 * 用于控制不同情况下 想灵活限制 process 是否启动
 * 如 本地调试 禁用不相关的自定义进程, CronCenter 模式禁用无关自定义进程
 * 甚至多节点部署时, 只在某些节点开启 自定义进程
 * ignore 忽略项, all 则忽略所有, 数组时忽略数组内指定自定义进程
 * active 活跃项, 忽略过滤后要启用的进程
 */

if (env('PROCESS_FILTER_ENABLE', false)) {
    return [
        'process' => [
            'ignore' => [
                'App\*',
            ],
        ],
        'amqp_consumer' => [
            'ignore' => 'all',
            'active' => [],
        ],
        'nsq_consumer' => [
            'ignore' => 'all',
        ],
    ];
}

return [];
