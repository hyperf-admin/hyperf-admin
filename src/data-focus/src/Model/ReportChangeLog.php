<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int            $id
 * @property int            $report_id   报表id
 * @property int            $dev_uid     开发者id
 * @property string         $dev_content 内容
 * @property int            $published   0未发布, 1已发布
 * @property \Carbon\Carbon $created_at
 */
class ReportChangeLog extends BaseModel
{
    protected $connection = 'data_focus';

    protected $table = 'report_change_log';

    protected $database = 'data_focus';

    protected $fillable = [
        'id',
        'report_id',
        'dev_uid',
        'dev_content',
        'published',
    ];

    protected $casts = [
        'report_id' => 'int',
        'dev_uid' => 'int',
        'dev_content' => 'string',
        'published' => 'int',
    ];
}
