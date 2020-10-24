<?php
declare(strict_types=1);
namespace HyperfAdmin\ConfigCenter\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $id
 * @property string         $path       存储位置
 * @property string         $value      节点值
 * @property int            $create_uid 创建者id
 * @property int            $is_locked  是否被锁定
 * @property string         $owner_uids 所有者
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ConfigCenter extends BaseModel
{
    protected $connection = 'hyperf_admin';

    protected $table = 'config_center';

    protected $fillable = ['id', 'path', 'value', 'create_uid', 'is_locked', 'owner_uids'];

    protected $casts = [
        'path' => 'string',
        'value' => 'array',
        'create_uid' => 'int',
        'is_locked' => 'int',
        'owner_uids' => 'string',
    ];
}
