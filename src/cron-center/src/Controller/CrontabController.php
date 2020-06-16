<?php
namespace HyperfAdmin\CronCenter\Controller;

use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Scaffold\Controller\AbstractController;
use HyperfAdmin\CronCenter\CronManager;
use HyperfAdmin\CronCenter\Model\CronJobs;

class CrontabController extends AbstractController
{
    protected $model_class = CronJobs::class;

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
            'form' => [
                'id|#' => 'int',
                'title|任务可读名' => 'required|max:100',
                'name|任务名' => [
                    'rule' => 'required|alpha_dash|max:100',
                    'info' => '可以包含字母和数字，以及破折号和下划线',
                ],
                'type|类型' => [
                    'type' => 'select',
                    'rule' => 'required',
                    'options' => CronJobs::$types,
                    'values' => [
                        'execute' => [
                            CronJobs::TYPE_CLASS,
                            CronJobs::TYPE_CMD,
                            CronJobs::TYPE_EVAL,
                        ],
                        'gateway' => [
                            CronJobs::TYPE_GATEWAY,
                        ],
                    ],
                ],
                'config.execute|执行入口' => [
                    'rule' => 'required_if:type,execute|max:100|call_class_exist',
                    'depend' => [
                        'field' => 'type',
                        'value' => [
                            CronJobs::TYPE_CLASS,
                            CronJobs::TYPE_CMD,
                            CronJobs::TYPE_EVAL,
                        ],
                    ],
                ],
                'config.api|API路由' => [
                    'rule' => 'required_if:type,gateway|max:100',
                    'depend' => [
                        'field' => 'type',
                        'value' => CronJobs::TYPE_GATEWAY,
                    ],
                ],
                'config.method|API请求方式' => [
                    'type' => 'select',
                    'rule' => 'required_if:type,gateway',
                    'options' => ['GET', 'POST'],
                    'default' => 'GET',
                    'depend' => [
                        'field' => 'type',
                        'value' => CronJobs::TYPE_GATEWAY,
                    ],
                ],
                'config.params|执行参数' => [
                    'type' => 'json',
                ],
                'config.headers|请求Headers' => [
                    'type' => 'json',
                    'depend' => [
                        'field' => 'type',
                        'value' => CronJobs::TYPE_GATEWAY,
                    ],
                ],
                'rule|运行规则' => [
                    'rule' => 'required|call_crontab',
                    'info' => '*    *    *    *    *    *',
                ],
                'status|状态' => [
                    'type' => 'select',
                    'rule' => 'required',
                    'options' => CronJobs::$status,
                    'default' => CronJobs::STATUS_NOT,
                ],
                'config.singleton|是否单例' => [
                    'type' => 'select',
                    'options' => [
                        YES => '是',
                        NO => '否',
                    ],
                    'default' => YES,
                    'info' => '解决任务的并发执行问题，任务永远只会同时运行 1 个。但是这个没法保障任务在集群时重复执行的问题。',
                ],
                'config.on_one_server|是否单节点' => [
                    'type' => 'select',
                    'options' => [
                        YES => '是',
                        NO => '否',
                    ],
                    'default' => YES,
                    'info' => '多实例部署项目时，则只有一个实例会被触发。',
                ],
                'bind_nodes|执行节点' => [
                    'type' => 'select',
                    'rule' => 'required',
                    'props' => [
                        'multiple' => true,
                        'limit' => 5,
                    ],
                    'options' => function () {
                        $nodes = $this->cron_manager->getAvailableNodes();
                        $options = [];
                        foreach ($nodes as $item) {
                            $options[] = [
                                'value' => $item['id'],
                                'label' => $item['name'],
                            ];
                        }

                        return $options;
                    },
                ],
                'alert_rule|报警规则' => [
                    "type" => 'json',
                ],
                'alert_receiver|报警接收人' => [
                    'rule' => 'number_concat_ws_comma',
                ],
            ],
            'table' => [
                'columns' => [
                    'id',
                    'title',
                    'name',
                    'type',
                    'rule',
                    [
                        'field' => 'status',
                        'enum' => [
                            YES => 'success',
                            NO => 'info',
                        ],
                    ],
                    [
                        'field' => 'state',
                        'hidden' => true,
                    ],
                    [
                        'field' => 'state_info',
                        'title' => '运行状态',
                        'virtual_field' => true,
                        'popover' => [
                            'messages' => [
                                '上线时间: {state.start_time}',
                                '最后活跃时间: {state.last_time}',
                                '运行次数: {state.counter}',
                            ],
                        ],
                        'render' => function () {
                            return '悬浮查看';
                        },
                    ],
                ],
                'rowActions' => [
                    ['action' => '/crontab/{id}', 'text' => '编辑',],
                    [
                        'action' => 'api',
                        'api' => '/crontab/trigger/{id}',
                        'text' => '触发',
                    ],
                    //[
                    //    'type' => 'drawer',
                    //    'target' => '',
                    //    'text' => '查看日志',
                    //    'props' => [
                    //        'component' => 'SocketList',
                    //        'componentProps' => [
                    //            'url' => env('OMS_WEBSOCKET_URL') . '/cronlog?name={name}',
                    //        ],
                    //        'drawerWithHeader' => false,
                    //        'drawerSize' => '80%',
                    //        'drawerTitle' => '{title}日志',
                    //        'drawerDirection' => 'ttb',
                    //    ],
                    //],
                ],
            ],
        ];
    }

    public function beforeSave($id, &$data)
    {
        $data['bind_nodes'] = implode(',', $data['bind_nodes']);
    }

    public function beforeFormResponse($id, &$record)
    {
        $record['bind_nodes'] = array_map('intval', explode(',', $record['bind_nodes']));
    }

    public function trigger($id)
    {
        $ret = $this->cron_manager->dispatch($id);

        return $ret === false ? $this->fail(ErrorCode::CODE_ERR_SYSTEM, "执行失败") : $this->success();
    }
}
