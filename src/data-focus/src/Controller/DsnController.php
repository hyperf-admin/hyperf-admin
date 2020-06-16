<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Controller;

use HyperfAdmin\BaseUtils\Scaffold\Controller\AbstractController;
use HyperfAdmin\DataFocus\Model\Dsn;

class DsnController extends AbstractController
{
    public $model_class = Dsn::class;

    public function scaffoldOptions()
    {
        return [
            'form' => [
                'id|#' => '',
                'type|类型' => [
                    'type' => 'select',
                    'options' => Dsn::$types,
                    'default' => Dsn::TYPE_MYSQL,
                ],
                'name|名称' => '',
                'remark|备注' => '',
                'config|配置' => [
                    'type' => 'json',
                ],
                'status|状态' => [
                    'type' => 'select',
                    'options' => Dsn::$status,
                    'default' => Dsn::STATUS_YES,
                ],
            ],
            'hasOne' => [
                'pt_oms.pt_oms.user:create_uid->id,realname',
            ],
            'table' => [
                'columns' => [
                    'id',
                    'name',
                    'type',
                    [
                        'field' => 'status',
                        'enum' => [
                            'info',
                            'success',
                        ],
                    ],
                    [
                        'field' => 'create_uid',
                        'title' => '创建者',
                        'render' => function ($val, $row) {
                            return $row['realname'];
                        },
                    ],
                ],
                'rowActions' => [
                    [
                        'type' => 'jump',
                        'target' => '/dsn/{id}',
                        'text' => '编辑',
                    ],
                    [
                        'type' => 'api',
                        'target' => '/dsn/{id}',
                        'text' => '删除',
                        'props' => [
                            'type' => 'danger',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function beforeSave($id, &$data)
    {
        if (!$id) {
            $data['create_uid'] = $this->userId();
        }
    }

    public function afterSave($id, $data, $entity)
    {
        make(\HyperfAdmin\DataFocus\Service\Dsn::class)->addToConfig($id);
    }
}
