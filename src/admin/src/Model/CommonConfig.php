<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property string $namespace
 * @property string $name
 * @property string $title
 * @property string $remark
 * @property string $rules
 * @property string $value
 * @property string $permissions
 * @property string $is_need_form
 */
class CommonConfig extends BaseModel
{
    protected $table = 'common_config';

    protected $connection = 'hyperf_admin';

    protected $fillable = [
        'namespace',
        'name',
        'title',
        'remark',
        'rules',
        'value',
        'permissions',
        'is_need_form',
    ];

    protected $casts = [
        'value' => 'array',
        'rules' => 'array',
        'is_need_form' => 'integer',
    ];

    const NEED_FORM_NO = 0;

    const NEED_FORM_YES = 1;

    public static $need_form = [
        self::NEED_FORM_NO => '否',
        self::NEED_FORM_YES => '是',
    ];
}
