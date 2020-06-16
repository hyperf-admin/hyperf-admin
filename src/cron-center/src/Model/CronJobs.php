<?php
declare (strict_types=1);
namespace HyperfAdmin\CronCenter\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property string $name
 * @property string $title
 * @property string $type
 * @property string $rule
 * @property string $alert_receiver
 * @property array  $alert_rule
 * @property string $bind_nodes
 * @property array  $config
 * @property int    $status
 * @property int    $is_deleted
 */
class CronJobs extends BaseModel
{
    protected $table = 'cron_jobs';

    protected $connection = 'cron_center';

    protected $fillable = [
        'name',
        'title',
        'type',
        'rule',
        'alert_receiver',
        'alert_rule',
        'bind_nodes',
        'config',
        'status',
        'is_deleted',
        'state',
    ];

    protected $casts = [
        'alert_rule' => 'array',
        'config' => 'array',
        'state' => 'array',
        'status' => 'integer',
        'is_deleted' => 'integer',
    ];

    const TYPE_CMD = 'command';

    const TYPE_CLASS = 'class';

    const TYPE_EVAL = 'eval';

    const TYPE_GATEWAY = 'gateway';

    public static $types = [
        self::TYPE_CLASS => '内部类',
        self::TYPE_CMD => '内部命令',
        self::TYPE_EVAL => '外部命令',
        self::TYPE_GATEWAY => '网关API',
    ];
}
