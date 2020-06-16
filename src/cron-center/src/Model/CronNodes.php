<?php
declare (strict_types=1);
namespace HyperfAdmin\CronCenter\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property string $name
 * @property int    $status
 * @property string $info
 */
class CronNodes extends BaseModel
{
    protected $table = 'cron_nodes';

    protected $connection = 'cron_center';

    protected $fillable = ['name', 'status', 'info'];

    protected $casts = ['status' => 'integer', 'info' => 'array'];

    const STATUS_BLOCK = 3;

    const STATUS_LOSS = 4;

    public static $status = [
        self::STATUS_NOT => '已下线',
        self::STATUS_YES => '运行中',
        self::STATUS_BLOCK => '锁定',
        self::STATUS_LOSS => '已失联',
    ];
}
