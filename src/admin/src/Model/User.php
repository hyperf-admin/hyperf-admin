<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $id
 * @property string         $username
 * @property string         $realname
 * @property string         $password
 * @property string         $mobile
 * @property string         $email
 * @property int            $status
 * @property string         $login_time
 * @property string         $login_ip
 * @property int            $is_admin
 * @property int            $is_default_pass
 * @property string         $qq
 * @property string         $roles
 * @property string         $sign
 * @property string         $avatar
 * @property string         $avatar_small
 * @property \Carbon\Carbon $create_at
 * @property \Carbon\Carbon $update_at
 */
class User extends BaseModel
{
    protected $connection = 'hyperf_admin';

    protected $table = 'user';

    protected $fillable = [
        'id',
        'username',
        'realname',
        'password',
        'mobile',
        'email',
        'status',
        'login_time',
        'login_ip',
        'is_admin',
        'is_default_pass',
        'qq',
        'roles',
        'sign',
        'avatar',
        'avatar_small',
    ];

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'status' => 'integer',
        'is_admin' => 'integer',
        'is_default_pass' => 'integer',
        'value' => 'integer',
    ];

    const USER_ENABLE = 1;

    const USER_DISABLE = 0;

    public static $status = [
        self::USER_DISABLE => '禁用',
        self::USER_ENABLE => '启用',
    ];

    public function getRolesAttribute($value)
    {
        return explode(',', $value);
    }

    public function setRolesAttribute($value)
    {
        return implode(',', $value);
    }

    public function getRealnameAttribute($value)
    {
        return $value ?: $this->username;
    }
}
