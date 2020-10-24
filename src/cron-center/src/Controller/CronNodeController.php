<?php
namespace HyperfAdmin\CronCenter\Controller;

use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Scaffold\Controller\AbstractController;
use HyperfAdmin\CronCenter\CronManager;
use HyperfAdmin\CronCenter\Model\CronNodes;

class CronNodeController extends AbstractController
{
    public $model_class = CronNodes::class;

    /**
     * @var CronManager
     */
    public $cron_manager;

    public function init()
    {
        $this->cron_manager = make(CronManager::class);
        parent::init();
    }

    public function scaffoldOptions()
    {
        return [
            'createAble' => false,
            'deleteAble' => false,
            'form' => [
                'id|#' => '',
                'name|节点名' => '',
                'status|状态' => [
                    'type' => 'select',
                    'options' => CronNodes::$status,
                    'enum' => [
                        YES => 'success',
                    ],
                ],
                'updated_at|最后活跃时间' => '',
            ],
            'table' => [
                'columns' => [
                    'id',
                    'name',
                    [
                        'field' => 'status',
                        'enum' => [
                            YES => 'success',
                            NO => 'info',
                            CronNodes::STATUS_BLOCK => 'warning',
                            CronNodes::STATUS_LOSS => 'danger',
                        ],
                        'render' => function ($status, $row) {
                            if ((time() - strtotime($row['updated_at'])) > 60) {
                                return CronNodes::STATUS_LOSS;
                            }

                            return $status;
                        },
                        'info' => '下线表示节点已经丢失, 不可用, 锁定表示节点存活, 但人为锁定, 将不执行任何任务',
                    ],
                    'updated_at',
                ],
                'rowActions' => [
                    [
                        'action' => 'api',
                        'api' => '/cronnodes/block/{id}',
                        'text' => '锁定',
                        'when' => ['status', '=', 1],
                    ],
                    [
                        'type' => 'api',
                        'target' => '/cronnodes/delete',
                        'text' => '删除',
                        'props' => [
                            'type' => 'danger',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function block($id)
    {
        $re = $this->cron_manager->blockNode($id);

        return $re ? $this->success() : $this->fail(ErrorCode::CODE_ERR_SYSTEM, '锁定失败');
    }
}
