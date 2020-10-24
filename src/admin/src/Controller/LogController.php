<?php
namespace HyperfAdmin\Admin\Controller;

use Carbon\Carbon;
use HyperfAdmin\Admin\Model\OperatorLog;

class LogController extends AdminAbstractController
{
    protected $model_class = OperatorLog::class;

    public function scaffoldOptions()
    {
        return [
            'exportAble' => true,
            'createAble' => false,
            'order_by' => 'id desc',
            'filter' => [
                'page_url',
                'page_name',
                'action',
                'operator_id',
                'relation_ids',
                'client_ip',
                'detail_json',
                'created_at',
            ],
            'formUI' => [
                'form' => [
                    'size' => 'mini',
                ],
            ],
            'form' => [
                'id|#' => 'required|int',
                'page_url|页面URL' => 'required|string',
                'page_name|页面名称' => 'required|string',
                'action|动作' => 'required|string',
                'relation_ids|处理ID' => 'required|string',
                'client_ip|客户端IP' => 'required|string',
                'operator_id|操作人' => [
                    'rule' => 'required|int',
                    'type' => 'select',
                    'props' => [
                        'allowCreate' => true,
                        'filterable' => true,
                        'remote' => true,
                        'selectApi' => '/search/user',
                    ],
                ],
                'detail_json|其他内容' => 'string',
                'created_at|记录时间' => [
                    'type' => 'date_range',
                ],
            ],
            'table' => [
                'columns' => [
                    [
                        'field' => 'operator_id',
                        'hidden' => true,
                    ],
                    ['field' => 'id', 'title' => 'ID', 'hidden' => true],
                    [
                        'field' => 'created_at',
                        'title' => '记录时间',
                        'width' => '150px',
                    ],
                    [
                        'field' => 'nickname',
                        'title' => '操作人',
                    ],
                    [
                        'field' => 'page_url',
                        'title' => '页面URL',
                        'width' => '150px',
                    ],
                    [
                        'field' => 'page_name',
                        'title' => '页面名称',
                        'width' => '220px',
                    ],
                    'action',
                    [
                        'field' => 'relation_ids',
                        'title' => '处理ID',
                        'width' => '150px',
                    ],
                    [
                        'field' => 'detail_json',
                        'hidden' => true,
                    ],
                    [
                        'field' => 'description',
                        'title' => '描述',
                        'virtual_field' => true,
                        'width' => '480px',
                        'render' => function ($field, $row) {
                            if (!is_array($row['detail_json'])) {
                                $row['detail_json'] = json_decode($row['detail_json'], true);
                            }

                            return $row['detail_json']['description'] ?? '';
                        },
                    ],
                    [
                        'field' => 'client_ip',
                        'title' => '客户端IP',
                        'width' => '100px',
                    ],
                    [
                        'field' => 'remark',
                        'title' => '备注',
                        'virtual_field' => true,
                        'width' => '200px',
                        'render' => function ($field, $row) {
                            if (!is_array($row['detail_json'])) {
                                $row['detail_json'] = json_decode($row['detail_json'], true);
                            }

                            return $row['detail_json']['remark'] ?? '';
                        },
                    ],
                ],
            ],
        ];
    }

    public function beforeListQuery(&$filters)
    {
        foreach ($filters as $field => $filter) {
            if (in_array($field, [
                'page_url',
                'page_name',
                'action',
                'detail_json',
                'relation_ids',
                'client_ip',
            ])) {
                $filters[$field] = ['like' => "%$filter%"];
            }
        }
        if (!empty($filters['created_at']['between'])) {
            $filters['created_at']['between'][0] = Carbon::parse($filters['created_at']['between'][0])->toDateTimeString();
            $filters['created_at']['between'][1] = Carbon::parse($filters['created_at']['between'][1] . ' +1 day last second')
                ->toDateTimeString();
        }
        unset($filters);
    }
}
