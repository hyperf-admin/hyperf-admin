<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $id
 * @property int            $pid             父id
 * @property string         $name            名称
 * @property string         $publish_content 发布的报表内容
 * @property string         $dev_content     开发中的报表内容
 * @property string         $bind_role_ids   授权的角色id
 * @property string         $bind_uids       绑定的用户id
 * @property int            $create_uid      创建者id
 * @property int            $dev_uid         开发者id
 * @property string         $crontab         定时任务
 * @property string         $config          配置
 * @property \Carbon\Carbon $publish_at      最后一次发布时间
 * @property \Carbon\Carbon $created_at       创建时间
 * @property \Carbon\Carbon $updated_at       最后更新时间
 */
class Reports extends BaseModel
{
    protected $connection = 'data_focus';

    protected $table = 'reports';

    protected $database = 'data_focus';

    protected $fillable = [
        'id',
        'name',
        'pid',
        'publish_content',
        'dev_content',
        'bind_role_ids',
        'bind_uids',
        'create_uid',
        'dev_uid',
        'crontab',
        'config',
        'publish_at',
    ];

    protected $casts = [
        'pid' => 'int',
        'name' => 'string',
        'publish_content' => 'string',
        'dev_content' => 'string',
        'bind_role_ids' => 'string',
        'bind_uids' => 'string',
        'create_uid' => 'int',
        'dev_uid' => 'int',
        'crontab' => 'string',
        'config' => 'string',
        'publish_at' => 'string',
    ];
}
