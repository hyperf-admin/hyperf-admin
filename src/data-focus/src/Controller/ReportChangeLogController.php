<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Controller;

use HyperfAdmin\BaseUtils\Scaffold\Controller\AbstractController;
use HyperfAdmin\DataFocus\Model\ReportChangeLog;

class ReportChangeLogController extends AbstractController
{
    public $model_class = ReportChangeLog::class;

    public function scaffoldOptions()
    {
        return [
            'form' => [
                'id|#' => '',
                'report_id|报表id' => '',
                'dev_uid|开发者id' => '',
                'dev_content|内容' => '',
                'published|0未发布, 1已发布' => '',
                'bind_uids|绑定的用户id' => '',
                'create_uid|创建者id' => '',
                'crontab|定时任务' => '',
                'config|配置' => '',
                'publish_at|最后一次发布时间' => [
                    'type' => 'datetime',
                ],
            ],
            'table' => [
                'rowActions' => [
                    [
                        'type' => 'jump',
                        'target' => '/reportchangelog/{id}',
                        'text' => '编辑',
                    ],
                    [
                        'type' => 'api',
                        'target' => '/reportchangelog/{id}',
                        'text' => '删除',
                        'props' => [
                            'type' => 'danger',
                        ],
                    ],
                ],
            ],
        ];
    }
}
