<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int    $id
 * @property string $name
 * @property string $list_api
 * @property array  $filters
 * @property int    $status
 * @property int    $total_pages
 * @property int    $current_page
 * @property int    $operator_id
 * @property string $download_url
 */
class ExportTasks extends BaseModel
{
    protected $table = 'export_tasks';

    protected $connection = 'hyperf_admin';

    protected $fillable = [
        'name',
        'list_api',
        'filters',
        'status',
        'total_pages',
        'current_page',
        'operator_id',
        'download_url',
    ];

    protected $casts = [
        'filters' => 'array',
        'status' => 'integer',
        'total_pages' => 'integer',
        'current_page' => 'integer',
        'operator_id' => 'integer',
    ];

    const STATUS_NOT_START = 0;

    const STATUS_PRE_PROCESSING = 10; // 预处理状态

    const STATUS_PROCESSING = 1;

    const STATUS_SUCCESS = 2;

    const STATUS_FAIL = 3;

    const LIMIT_SIZE_MAX = 30000; // 导出最大条数
}
