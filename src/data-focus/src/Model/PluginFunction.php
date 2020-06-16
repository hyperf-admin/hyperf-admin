<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $id
 * @property string         $name       中文名称
 * @property string         $func_name  方法名
 * @property string         $context    方法体定义
 * @property int            $create_uid 创建者id
 * @property int            $status     状态
 * @property \Carbon\Carbon $create_at
 * @property \Carbon\Carbon $update_at
 */
class PluginFunction extends BaseModel
{
    protected $connection = 'data_focus';

    protected $table = 'plugin_function';

    protected $database = 'mdata_focus';

    protected $fillable = [
        'id',
        'name',
        'type',
        'func_name',
        'context',
        'create_uid',
        'status',
    ];

    protected $casts = [
        'name' => 'string',
        'func_name' => 'string',
        'context' => 'string',
        'create_uid' => 'int',
        'type' => 'int',
        'status' => 'int',
    ];

    const TYPE_COLUMN = 1;

    const TYPE_TABLE = 2;

    public static $type = [
        self::TYPE_COLUMN => '行插件',
        self::TYPE_TABLE => '表插件',
    ];
}
