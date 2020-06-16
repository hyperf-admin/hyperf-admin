<?php
namespace HyperfAdmin\DevTools\Controller;

use Hyperf\Utils\Str;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Scaffold\Controller\AbstractController;
use HyperfAdmin\DevTools\ControllerMaker;
use HyperfAdmin\DevTools\ModelMaker;
use HyperfAdmin\DevTools\TableSchema;

class DevController extends AbstractController
{
    public $model_class = '';

    public function rules()
    {
        return [
            'pool|连接池' => [
                'type' => 'select',
                'rule' => 'required',
                'options' => function ($field, $biz) {
                    $pools = array_keys(config('databases'));
                    $options = [];
                    foreach ($pools as $pool) {
                        if (Str::startsWith($pool, '_')) {
                            continue;
                        }
                        $options[] = [
                            'value' => $pool,
                            'label' => $pool,
                        ];
                    }

                    return $options;
                },
                'col' => [
                    'span' => 8,
                ],
            ],
            'database|数据库' => [
                'type' => 'select',
                'rule' => 'required',
                'props' => [
                    'selectApi' => '/dev/dbAct?pool={pool}',
                ],
                'col' => [
                    'span' => 8,
                ],
            ],
            'table|表' => [
                'type' => 'select',
                'rule' => 'required',
                'props' => [
                    'selectApi' => '/dev/tableAct?pool={pool}&db={database}',
                ],
                'col' => [
                    'span' => 8,
                ],
            ],
            'model_path|Model路径' => [
                'rule' => 'required',
                'default' => 'app/Model',
                'col' => [
                    'span' => 12,
                ],
            ],
            'controller_path|Controller路径' => [
                'rule' => 'required',
                'default' => 'app/Controller',
                'col' => [
                    'span' => 12,
                ],
            ],
            'base_init|基础初始化' => [
                'type' => 'checkbox',
                'options' => [
                    'createAble' => '渲染创建按钮',
                    'exportAble' => '渲染导出按钮',
                    'deleteAble' => '允许删除',
                    'defaultList' => '首屏列表默认查询',
                    'filterSyncToQuery' => '筛选同步URL',
                    'editButton' => '行编辑按钮',
                    'deleteButton' => '行删除按钮',
                ],
                'default' => [
                    'createAble',
                    'exportAble',
                    'deleteAble',
                    'defaultList',
                    'filterSyncToQuery',
                    'editButton',
                    'deleteButton',
                ],
            ],
            'init_hooks|钩子函数初始化' => [
                'type' => 'checkbox',
                'options' => [
                    'beforeInfo' => 'beforeInfo',
                    'beforeListQuery' => 'beforeListQuery',
                    'beforeListResponse' => 'beforeListResponse',
                    'meddleFormRule' => 'meddleFormRule',
                    'beforeFormResponse' => 'beforeFormResponse',
                    'beforeSave' => 'beforeSave',
                    'afterSave' => 'afterSave',
                    'beforeDelete' => 'beforeDelete',
                    'afterDelete' => 'afterDelete',
                ],
                'props' => [
                    'multiple' => true,
                ],
                'info' => '钩子函数的具体含义请查看文档',
                'default' => [],
            ],
            'form|表单' => [
                'type' => 'sub-form',
                'rule' => 'required',
                'children' => [
                    'field|字段名' => [
                        'rule' => 'required',
                        'col' => [
                            'span' => 8,
                        ],
                    ],
                    'label|字段中文名' => [
                        'col' => [
                            'span' => 8,
                        ],
                    ],
                    'type|字段类型' => [
                        'type' => 'select',
                        'options' => [
                            'input' => 'input',
                            'hidden' => 'hidden',
                            'select' => 'select',
                            'number' => 'number',
                            'float' => 'float',
                            'radio' => 'radio',
                            'switch' => 'switch',
                            'date' => 'date',
                            'date_time' => 'date_time',
                            'date_range' => 'date_range',
                            'datetime_range' => 'datetime_range',
                            'image' => 'image',
                            'file' => 'file',
                        ],
                        'default' => 'input',
                        'col' => [
                            'span' => 8,
                        ],
                    ],
                    //'options' => [
                    //    'type' => 'json',
                    //    'depend' => [
                    //        'field' => 'type',
                    //        'value' => ['select', 'checkbox', 'radio']
                    //    ]
                    //],
                    'rule|校验规则' => [
                        'type' => 'select',
                        'props' => [
                            'multiple' => true,
                        ],
                        'options' => [
                            'required' => 'required',
                            'alpha' => 'alpha',
                            'alpha_dash' => 'alpha_dash',
                            'alpha_num' => 'alpha_num',
                            'array' => 'array',
                            'url' => 'url',
                        ],
                        'col' => [
                            'span' => 8,
                        ],
                    ],
                    'default|默认值' => [
                        'col' => [
                            'span' => 8,
                        ],
                    ],
                    'virtual_field|虚拟字段' => [
                        'type' => 'radio',
                        'options' => [
                            0 => '否',
                            1 => '是',
                        ],
                        'col' => [
                            'span' => 8,
                        ],
                        'default' => 0,
                    ],
                    'props' => [
                        'type' => 'json',
                    ],
                ],
                'repeat' => true,
                'props' => [
                    'sort' => true,
                ],
            ],
        ];
    }

    public function controllerMaker()
    {
        $rule = $this->rules();
        $form = $this->formOptionsConvert($rule, false, false);
        $compute_map = $this->formComputeConfig($form);

        return $this->success([
            'form' => $form,
            'compute_map' => (object)$compute_map,
        ]);
    }

    public function make()
    {
        if (!is_dev()) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '该功能仅限开发环境使用');
        }
        $rules = $this->getFormRules($this->rules());
        $data_source = $this->request->all();
        [
            $data,
            $errors,
        ] = $this->validation->check($rules, $data_source, $this);
        if ($errors) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, implode(PHP_EOL, $errors));
        }
        /** @var ModelMaker $model_maker */
        $model_maker = make(ModelMaker::class);
        $model_class = $model_maker->make($data['pool'], $data['database'], $data['table'], $data['model_path']);
        if ($model_class === false) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, 'Model创建失败');
        }
        /** @var ControllerMaker $ctl_maker */
        $ctl_maker = make(ControllerMaker::class);
        $controller_name = $ctl_maker->make($model_class, $data['controller_path'], $data);
        if ($controller_name === false) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, 'Controller创建失败');
        }
        $msg = [$model_class, $controller_name, '创建成功'];

        return $this->success([], implode("\n", $msg));
    }

    public function dbAct()
    {
        $pool = $this->request->input('pool');
        /** @var TableSchema $tool */
        $tool = make(TableSchema::class);
        $dbs = $tool->getDbs($pool);
        $options = [];
        foreach ($dbs as $db) {
            $options[] = [
                'value' => $db,
                'label' => $db,
            ];
        }

        return $this->success($options);
    }

    public function tableAct()
    {
        $pool = $this->request->input('pool');
        $db = $this->request->input('db');
        /** @var TableSchema $tool */
        $tool = make(TableSchema::class);
        $dbs = $tool->databasesTables($pool, $db);
        $options = [];
        foreach ($dbs as $db) {
            $options[] = [
                'value' => $db,
                'label' => $db,
            ];
        }

        return $this->success($options);
    }

    public function tableSchema()
    {
        $pool = $this->request->input('pool');
        $db = $this->request->input('db');
        $table = $this->request->input('table');
        /** @var TableSchema $tool */
        $tool = make(TableSchema::class);
        $schema = $tool->tableSchema($pool, $db, $table);
        $ret = [];
        $ignores = ['id', 'create_at', 'update_at', 'is_deleted'];
        foreach ($schema as $item) {
            if (in_array($item['column_name'], $ignores)) {
                continue;
            }
            $ret[] = [
                'field' => $item['column_name'],
                'label' => $item['column_comment'],
                'type' => $this->transType($item['data_type']),
            ];
        }

        return $this->success($ret);
    }

    public function transType($type)
    {
        switch ($type) {
            case 'datetime':
                return 'datetime';
            case 'bigint':
                return 'number';
            default:
                return 'input';
        }
    }
}
