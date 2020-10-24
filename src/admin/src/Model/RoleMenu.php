<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $role_id
 * @property int            $router_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RoleMenu extends BaseModel
{
    protected $table = 'role_menus';

    protected $connection = 'hyperf_admin';

    protected $fillable = [
        'role_id',
        'router_id',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'router_id' => 'integer',
    ];
}
