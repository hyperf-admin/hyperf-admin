<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int    $pid
 * @property string $label
 * @property string $module
 * @property string $path
 * @property string $icon
 * @property int    $open_type
 * @property int    $is_menu
 * @property int    $state
 * @property int    $sort
 */
class FrontRoutes extends BaseModel
{
    protected $table = 'front_routes';

    protected $connection = 'hyperf_admin';

    protected $fillable = [
        'pid',
        'label',
        'module',
        'path',
        'view',
        'icon',
        'open_type',
        'is_menu',
        'status',
        'is_scaffold',
        'sort',
        'type',
        'permission',
        'http_method',
        'page_type',
    ];

    protected $casts = [
        'pid' => 'integer',
        'open_type' => 'integer',
        'type' => 'integer',
        'is_menu' => 'integer',
        'status' => 'integer',
        'is_scaffold' => 'integer',
        'page_type' => 'integer',
        'sort' => 'integer',
    ];

    const HTTP_METHOD_ANY = 0;

    const HTTP_METHOD_GET = 1;

    const HTTP_METHOD_POST = 2;

    const HTTP_METHOD_PUT = 3;

    const HTTP_METHOD_DELETE = 4;

    const PAGE_TYPE_LIST = 0;

    const PAGE_TYPE_FORM = 1;

    const IS_MENU = 1;

    const IS_NOT_MENU = 0;

    const RESOURCE_OPEN = 0;

    const RESOURCE_NEED_AUTH = 1;

    public static $http_methods = [
        self::HTTP_METHOD_ANY => 'ANY',
        self::HTTP_METHOD_GET => 'GET',
        self::HTTP_METHOD_POST => 'POST',
        self::HTTP_METHOD_PUT => 'PUT',
        self::HTTP_METHOD_DELETE => 'DELETE',
    ];

    public function scopeDefaultSelect($query)
    {
        return $query->select([
            'id',
            'pid',
            'label as menu_name',
            'is_menu as hidden',
            'is_scaffold as scaffold',
            'path as url',
            'view',
            'icon',
            'sort',
        ]);
    }

    public function scopeOnlyMenu($query)
    {
        return $query->where('is_menu', YES);
    }
}
