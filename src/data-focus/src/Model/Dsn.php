<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $id
 * @property int            $type       类型 1mysql
 * @property string         $name       名称
 * @property string         $remark     备注
 * @property array          $config     配置
 * @property int            $status     状态
 * @property int            $create_uid 创建者id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Dsn extends BaseModel
{
    protected $connection = 'data_focus';

    protected $table = 'dsn';

    protected $database = 'data_focus';

    protected $fillable = [
        'id',
        'type',
        'name',
        'remark',
        'config',
        'create_uid',
        'status',
    ];

    protected $casts = [
        'name' => 'string',
        'remark' => 'string',
        'config' => 'array',
        'create_uid' => 'int',
        'status' => 'int',
    ];

    const TYPE_MYSQL = 1;

    const TYPE_REDIS = 2;

    public static $types = [
        self::TYPE_MYSQL => "MySql",
        self::TYPE_REDIS => 'Redis',
    ];
}
