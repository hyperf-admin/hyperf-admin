<?php
declare(strict_types=1);
namespace HyperfAdmin\ConfigCenter\Controller;

use HyperfAdmin\Admin\Controller\AdminAbstractController;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\ConfigCenter\Model\ConfigCenter;
use HyperfAdmin\ConfigCenter\Service\ConfigCenterService;

class ConfigCenterController extends AdminAbstractController
{
    public $model_class = ConfigCenter::class;

    public function scaffoldOptions()
    {
        return [
            'exportAble' => false,
            'filter' => [
                'path%'
            ],
            'form' => [
                'id|#' => '',
                'path|存储位置' => [
                    'rule' => 'required',
                ],
                'value|节点值' => [
                    'type' => 'json',
                    'rule' => 'required',
                ],
                'create_uid|创建者id' => [
                    'form' => false,
                ],
                'is_locked|是否被锁定' => [
                    'type' => 'radio',
                    'options' => [
                        ConfigCenter::STATUS_YES => '锁定',
                        ConfigCenter::STATUS_NOT => '未锁定',
                    ],
                    'default' => ConfigCenter::STATUS_NOT,
                    'info' => '锁定后, 将只有所有者才可以修改该配置',
                ],
                'owner_uids|所有者' => [
                    'type' => 'select',
                    'props' => [
                        'selectApi' => '/user/act',
                        'multiple' => true,
                        'limit' => 5,
                    ],
                ],
            ],
            'hasOne' => [
                sprintf('hyperf_admin.%s.user:create_uid->id,realname', env('HYPERF_ADMIN_DB_NAME')),
            ],
            'table' => [
                'columns' => [
                    'id',
                    'path',
                    'value',
                    [
                        'field' => 'is_locked',
                        'enum' => [
                            ConfigCenter::STATUS_YES => 'success',
                            ConfigCenter::STATUS_NOT => 'info',
                        ],
                    ],
                    [
                        'field' => 'create_uid',
                        'hidden' => true,
                    ],
                    [
                        'field' => 'realname',
                        'title' => '创建者',
                        'virtual_field' => true,
                    ],
                ],
                'rowActions' => [
                    [
                        'type' => 'jump',
                        'target' => '/config_center/{id}',
                        'text' => '编辑',
                    ],
                    [
                        'type' => 'api',
                        'target' => '/config_center/delete',
                        'text' => '删除',
                        'props' => [
                            'type' => 'danger',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function beforeFormResponse($id, &$data)
    {
        $data['owner_uids'] = array_filter(array_map('intval', explode(',', $data['owner_uids'])));
    }

    public function beforeSave($id, &$data)
    {
        if ($data['owner_uids'] === '') {
            $data['owner_uids'] = [];
        }
        if (!$id) {
            $data['create_uid'] = $this->userId();
            $data['owner_uids'][] = $this->userId();
        } else {
            $old = $this->getEntity()->get($id);
            if ($old['is_locked'] && !in_array($this->userId(), $data['owner_uids'])) {
                return $this->fail(ErrorCode::CODE_ERR_AUTH, '配置已被锁定, 您无权更');
            }
        }

        $data['owner_uids'] = implode(',', array_unique($data['owner_uids']));
    }

    public function afterSave($pk_val, $data, $entity)
    {
        make(ConfigCenterService::class)->save();
    }
}
