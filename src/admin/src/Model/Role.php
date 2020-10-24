<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $id
 * @property int            $pid
 * @property string         $name
 * @property int            $status
 * @property int            $sort
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Role extends BaseModel
{
    protected $table = 'roles';

    protected $connection = 'hyperf_admin';

    protected $fillable = [
        'name',
        'pid',
        'status',
        'sort',
        'permissions',
    ];

    protected $casts = [
        'status' => 'integer',
        'permissions' => 'array',
        'sort' => 'integer',
        'name' => 'string',
        'pid' => 'integer',
        'id' => 'integer',
        'value' => 'integer',
    ];

    public function menus()
    {
        return $this->hasMany('App\System\Model\MtOms\RoleMenu', 'role_id');
    }

    public function resources()
    {
        return $this->hasMany('App\System\Model\MtOms\RoleResource', 'role_id');
    }
}
