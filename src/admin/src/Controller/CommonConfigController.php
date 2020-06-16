<?php

namespace HyperfAdmin\Admin\Controller;

use HyperfAdmin\Admin\Model\CommonConfig;
use HyperfAdmin\Admin\Service\CommonConfig as CommonConfigService;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;

class CommonConfigController extends AdminAbstractController
{
    protected $model_class = CommonConfig::class;

    public function scaffoldOptions()
    {
        return [
            'filter' => ['name%'],
            'form' => [
                'id|#' => '',
                'namespace|命名空间' => [
                    'rule' => 'required',
                    'type' => 'select',
                    'options' => function ($field, $data) {
                        $namespaces = $this->getModel()->where(['name' => 'namespace'])->value('value');
                        $options = [];
                        foreach ($namespaces as $value => $label) {
                            $options[] = [
                                'value' => $value,
                                'label' => $label,
                            ];
                        }

                        return $options;
                    },
                    'default' => 'common',
                ],
                'name|名称' => [
                    'rule' => 'required|unique:hyperf_admin.common_config,name',
                    'readonly' => true,
                ],
                'title|可读名称' => [
                    'rule' => 'required',
                ],
                'rules|规则' => [
                    'type' => 'json',
                    'rule' => 'json',
                    'depend' => [
                        'field' => 'is_need_form',
                        'value' => CommonConfig::NEED_FORM_YES,
                    ],
                ],
                'remark|备注' => 'max:100',
                'is_need_form|是否使用表单' => [
                    'rule' => 'integer',
                    'type' => 'radio',
                    'options' => CommonConfig::$need_form,
                    'default' => CommonConfig::NEED_FORM_YES,
                ],
                'value|配置值' => [
                    'type' => 'json',
                    'rule' => 'json',
                    'depend' => [
                        'field' => 'is_need_form',
                        'value' => CommonConfig::NEED_FORM_NO,
                    ],
                ],
            ],
            'table' => [
                'columns' => [
                    'id',
                    [
                        'field' => 'namespace',
                        'enum' => [
                            'default' => '通用',
                        ],
                    ],
                    'name',
                    'title',
                    [
                        'field' => 'is_need_form',
                        'hidden' => true,
                    ],
                ],
                'rowActions' => [
                    ['action' => '/cconf/{id}', 'text' => '编辑'],
                    [
                        'action' => '/cconf/cconf_{name}',
                        'text' => '表单',
                        'when' => [
                            ['is_need_form', '=', CommonConfig::NEED_FORM_YES],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function detail($key)
    {
        $conf = CommonConfigService::getConfigByName($key);
        if (!$conf || !$conf['rules']) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, '通用配置未找到 ' . $key);
        }
        $rules = $this->formOptionsConvert($conf['rules'], true, false, false, $conf['value']);
        $compute_map = $this->formComputeConfig($rules);

        return $this->success([
            'form' => $rules,
            'compute_map' => (object)$compute_map,
        ]);
    }

    public function saveDetail($key)
    {
        $conf = CommonConfig::query()->where(['name' => $key])->select([
            'id',
            'rules',
        ])->first();
        if (!$conf) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, '参数错误');
        }

        $saved = $conf->fill(['value' => $this->request->all()])->save();

        return $saved ? $this->success() : $this->fail(ErrorCode::CODE_ERR_SYSTEM);
    }
}
