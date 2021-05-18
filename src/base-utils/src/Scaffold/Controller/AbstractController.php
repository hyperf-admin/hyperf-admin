<?php
namespace HyperfAdmin\BaseUtils\Scaffold\Controller;

use Hyperf\DbConnection\Db;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Model\BaseModel;
use HyperfAdmin\BaseUtils\Model\EsBaseModel;
use HyperfAdmin\BaseUtils\Scaffold\Entity\EsEntityAbstract;
use HyperfAdmin\BaseUtils\Scaffold\Entity\MysqlEntityAbstract;

abstract class AbstractController extends Controller
{
    /**
     * 脚手架操作的model对象名
     *
     * @var string
     */
    protected $entity_class;

    /**
     * entity实例
     *
     * @var \HyperfAdmin\BaseUtils\Scaffold\Entity\EntityInterface
     */
    protected $entity;

    /**
     * 脚手架操作的model对象名
     *
     * @var string
     */
    protected $model_class;

    /**
     * model实例
     *
     * @var \HyperfAdmin\BaseUtils\Model\BaseModel
     */
    protected $model;

    /**
     * 根据 scaffoldOptions 生成的配置项
     *
     * @var array
     */
    protected $options = [];

    /**
     * 版本控制中默认版本获取数
     *
     * @var int
     */
    public $previous_version_number = 10;

    /**
     * 脚手架初始化
     */
    public function init()
    {
        $this->options = $this->scaffoldOptions();
    }

    /**
     * 脚手架配置
     *
     * @return array|callable
     */
    public function scaffoldOptions()
    {
        return [];
    }

    /**
     * 列表页拉取配置接口
     */
    public function info()
    {
        $tableHeader = array_values(array_filter($this->getListHeader(), function ($item) {
            return !($item['hidden'] ?? false);
        }));
        $filter = $this->getListFilters();
        $actions = $this->options['table']['rowActions'] ?? [];
        $actions = $this->buttonConfigConvert($actions);
        $topActions = $this->options['table']['topActions'] ?? [];
        $topActions = $this->buttonConfigConvert($topActions);
        $batchButtons = $this->options['table']['batchButtons'] ?? [];
        $batchButtons = $this->buttonConfigConvert($batchButtons);
        $enum = $this->options['table']['enum'] ?? [];
        $resource = $this->getCalledSource(true);
        $tabs = $this->options['table']['tabs'] ?? [];
        $info = [
            'filterRule' => $filter,
            'tableHeader' => $tableHeader,
            'rowActions' => $actions,
            'tableTabs' => is_callable($tabs) ? $tabs() : $tabs,
            'options' => [
                'form_path' => $this->options['form_path'] ?? '',
                'rowChangeApi' => "/{$resource[0]}/rowchange/{id}",
                'batchButtons' => $batchButtons,
                'enum' => $enum,
                'createAble' => $this->options['createAble'] ?? true,
                'exportAble' => $this->options['exportAble'] ?? true,
                'defaultList' => $this->options['defaultList'] ?? true,
                'importAble' => $this->options['importAble'] ?? false,
                'topActions' => $topActions,
                'tableOptions' => [
                    'style' => $this->options['table']['style'] ?? 'list',
                    'group' => $this->options['table']['group'] ?? [],
                ],
                'noticeAble' => !empty($this->options['notices'] ?? []),
                'page'=>$this->options['table']['page'] ?? [20,40,60,80,100]
            ],
        ];
        if (method_exists($this, 'beforeInfo')) {
            $info = $this->beforeInfo($info);
        }
        return $this->success($info);
    }

    public function buttonConfigConvert($config)
    {
        $buttons = [];
        foreach ($config as $key => $item) {
            $buttons[$key]['text'] = $item['text'] ?? '';
            $buttons[$key]['type'] = isset($item['target']) ? $item['type'] ?? 'jump' : (isset($item['rules']) ? 'form' : (isset($item['api']) ? 'api' : 'jump'));
            $buttons[$key]['target'] = isset($item['target']) ? $item['target'] : (isset($item['api']) ? $item['api'] : ($item['action'] ?? ''));
            $buttons[$key]['props'] = isset($item['target']) ? $item['props'] ?? [] : [
                'icon' => $item['icon'] ?? '',
                'circle' => $item['circle'] ?? false,
                'size' => $item['size'] ?? 'small',
                'type' => $item['type'] ?? '',
            ];
            if (isset($item['rules'])) {
                $form = $this->formOptionsConvert($item['rules']);
                $buttons[$key]['rules'] = $this->formResponse(0, $form);
                unset($form);
            }
            $buttons[$key]['rules']['form_ui'] = $item['formUi'] ?? [];
            if (isset($item['when'])) {
                $buttons[$key]['when'] = $item['when'];
            }
            // 批量操作的数据过滤
            if (isset($item['selectFilter'])) {
                $buttons[$key]['selectFilter'] = $item['selectFilter'];
            }
            if (isset($item[0])) {
                $buttons[$key] = $this->buttonConfigConvert($item);
            }
            if (isset($item['method'])) {
                $buttons[$key]['method'] = $item['method'];
            }
         }
        return $buttons;
    }

    public function makeWhere()
    {
        $page = $this->request->input('_page', 1);
        $size = $this->request->input('_size', 20);
        $table_options = $this->getListHeader();
        $columns = array_unique(array_values(array_map(function ($item) {
            return explode('.', $item)[0];
        }, array_column(array_filter($table_options, function ($each) {
            return isset($each['virtual_field']) ? !$each['virtual_field'] : true;
        }), 'field'))));
        $filter_options = $this->getListFilters();
        $filters = [];
        if (!empty($filter_options)) {
            array_change_v2k($filter_options, 'field');
            foreach ($filter_options as $field => $each) {
                $input = $this->request->input($field);
                if (in_array($input, [null, ''])) {
                    continue;
                }
                // todo field 相同时覆盖的问题
                if (isset($each['filterConvert'])) {
                    $filters[$each['filterConvert']['field']] = $each['filterConvert']['handel']($input);
                } else {
                    $filters[$field] = $input;
                }
            }
        }
        $conditions = $this->options['where'] ?? [];
        foreach ($filters as $field => $value) {
            switch ($filter_options[$field]['search_type'] ?? '') {
                case 'between':
                    $conditions[$field] = ['between' => $value];
                    break;
                case 'full_like':
                    $conditions[$field] = ['like' => "%{$value}%"];
                    break;
                case 'suffix_like':
                    $conditions[$field] = ['like' => "{$value}%"];
                    break;
                case 'prefix_like':
                    $conditions[$field] = ['like' => "%{$value}"];
                    break;
                default:
                    $conditions[$field] = $value;
                    break;
            }
        }
        $order_by = $this->options['order_by'] ?? '';
        if ($sortColumn = $this->request->input('_sort_column') && $sortType = $this->request->input('_sort_type')) {
            $order_by = $sortColumn . ' ' . $sortType;
        }
        if (empty($conditions) && !($this->options['defaultList'] ?? true)) {
            return compact('page', 'size', 'conditions', 'order_by', 'columns', 'table_options');
        }
        if (method_exists($this, 'beforeListQuery')) {
            $hook_params = get_class_method_params_name($this, 'beforeListQuery');
            if (count($hook_params) == 2) {
                $this->beforeListQuery($conditions, $order_by);
            } elseif (count($hook_params) === 1) {
                $this->beforeListQuery($conditions);
            }
        }
        return compact('page', 'size', 'conditions', 'order_by', 'columns', 'table_options');
    }

    /**
     * 列表拉取接口
     */
    public function list()
    {
        [
            $page,
            $size,
            $conditions,
            $order_by,
            $columns,
            $tableOptions,
        ] = array_values($this->makeWhere());
        $entity = $this->getEntity();
        $count = $entity->count($conditions);
        $list = [];
        if ($count) {
            $attr['select'] = $columns;
            $order_by && $attr['order_by'] = $order_by;
            $list = $entity->list($conditions, $attr, $page, $size);
        }
        $list = $this->listFilter($list, $tableOptions);
        return $this->success([
            'list' => $list,
            'total' => $count,
        ]);
    }

    public function listFilter($list, $table_options)
    {
        foreach ($this->options['hasOne'] ?? [] as $item) {
            $execute = $this->hasOne($list, $item);
            $execute && $list = $execute;
        }
        foreach ($this->options['hasMany'] ?? [] as $item) {
            $execute = $this->hasMany($list, $item);
            $execute && $list = $execute;
        }
        if (method_exists($this, 'beforeListResponse')) {
            $this->beforeListResponse($list);
        }
        $is_tree = $this->options['table']['is_tree'] ?? false;
        $is_tree && $this->hasChildren($list);
        foreach ($table_options as $item) {
            if (!isset($item['render'])) {
                continue;
            }
            foreach ($list as &$each) {
                if (is_callable($item['render'])) {
                    $each[$item['field']] = $item['render']($each[$item['field']] ?? null, $each);
                } elseif (is_string($item['render']) && method_exists($this, $item['render'])) {
                    $each[$item['field']] = $this->{$item['render']}($each[$item['field']] ?? null, $each);
                }
                unset($each);
            }
        }
        return $list;
    }

    /**
     * @param array  $list
     * @param string $has_str [pool.]db.table:[local_key->]foreign_key,other_key
     *
     * @return mixed
     */
    public function hasOne($list, $has_str)
    {
        $explode = $this->explodeHasStr($has_str);
        if (!$explode) {
            return false;
        }
        [
            $pool,
            $db,
            $table,
            $local_key,
            $foreign_key,
            $columns,
            $default,
        ] = $explode;
        $where = array_filter(array_column($list, $local_key));
        if (!$where) {
            return false;
        }
        $ret = Db::connection($pool)->table("{$db}.{$table}")->whereIn($foreign_key, $where)->get($columns)->toArray();
        array_change_v2k($ret, $foreign_key);
        foreach ($list as &$item) {
            $append = isset($ret[$item[$local_key]]) ? $ret[$item[$local_key]] : $default;
            unset($append[$foreign_key]);
            $item = array_merge($item, $append);
        }
        unset($item);
        return $list;
    }

    public function hasMany($list, $has_str)
    {
        $explode = $this->explodeHasStr($has_str);
        if (!$explode) {
            return false;
        }
        [
            $pool,
            $db,
            $table,
            $local_key,
            $foreign_key,
            $columns,
            $default,
        ] = $explode;
        $where = array_column($list, $local_key);
        if (!$where) {
            return false;
        }
        $ret = Db::connection($pool)->table("{$db}.{$table}")->whereIn($foreign_key, $where)->get($columns)->toArray();
        $ret = array_group_by($ret, $foreign_key);
        foreach ($list as &$item) {
            $group = isset($ret[$item[$local_key]]) ? $ret[$item[$local_key]] : $default;
            $append = [];
            foreach (array_keys($default) as $field) {
                $append[$field] = array_values(array_unique(array_filter(array_column($group, $field))));
            }
            unset($append[$foreign_key]);
            $item = array_merge($item, $append);
        }
        unset($item);
        return $list;
    }

    public function explodeHasStr($has_str)
    {
        $check = preg_match('/([a-zA-Z_0-9]+\.)?([a-zA-Z_0-9]+)\.([a-zA-Z_0-9]+):([a-zA-Z_0-9]+->)?([a-zA-Z_,0-9 ]+)/', $has_str, $match);
        if ($check === 0) {
            return false;
        }
        [
            $str,
            $pool,
            $db,
            $table,
            $local_key,
            $foreign_key,
        ] = array_map(function ($item) {
            return str_replace(['.', '->'], '', $item);
        }, $match);
        $pool = $pool ? $pool : 'default';
        $local_key = $local_key ? $local_key : 'id';
        $columns = explode(',', $foreign_key);
        if (!$columns) {
            return false;
        }
        $foreign_key = $columns[0];
        $default = [];
        foreach ($columns as $each) {
            $default[trim(preg_replace('/[\w ]+as +/i', '', trim($each)))] = null;
        }
        return array_values(compact('pool', 'db', 'table', 'local_key', 'foreign_key', 'columns', 'default'));
    }

    public function getTreeNodeChilds($id)
    {
        $tableOptions = $this->getListHeader();
        $columns = array_unique(array_values(array_map(function ($item) {
            return explode('.', $item)[0];
        }, array_column(array_filter($tableOptions, function ($each) {
            return isset($each['virtual_field']) ? !$each['virtual_field'] : true;
        }), 'field'))));
        $order_by = $this->options['order_by'] ?? '';
        $attr['select'] = $columns;
        $order_by && $attr['order_by'] = $order_by;
        $parent_key = $this->options['table']['tree']['pid'] ?? 'pid';
        $childs = $this->getEntity()->list([$parent_key => $id], $attr);
        foreach ($tableOptions as $item) {
            if (!isset($item['render'])) {
                continue;
            }
            foreach ($childs as &$each) {
                if (is_callable($item['render'])) {
                    $each[$item['field']] = $item['render']($each[$item['field']] ?? null, $each);
                } elseif (is_string($item['render']) && method_exists($this, $item['render'])) {
                    $each[$item['field']] = $this->$item['render']($each[$item['field']] ?? null, $each);
                }
                unset($each);
            }
        }
        $is_tree = $this->options['table']['is_tree'] ?? false;
        $is_tree && $this->hasChildren($childs);
        if (method_exists($this, 'beforeListResponse')) {
            $this->beforeListResponse($childs);
        }
        return $this->success([
            'childs' => $childs,
        ]);
    }

    public function hasChildren(&$list)
    {
        if (!$list) {
            return;
        }
        $pk = $this->getPk();
        $ids = array_column($list, $pk);
        $parent_key = $this->options['table']['tree']['pid'] ?? 'pid';
        $childs = $this->getEntity()->list([
            $parent_key => $ids,
            'status' => 1,
        ], [
            'select' => [$parent_key, "count(*) as hasChildren"],
            'group_by' => $parent_key,
        ]);
        array_change_v2k($childs, $parent_key);
        foreach ($list as &$item) {
            $item['hasChildren'] = (bool)($childs[$item[$pk]] ?? false);
            unset($item);
        }
        unset($list);
    }

    /**
     * 获取当前操作model对象
     *
     * @return \HyperfAdmin\Util\Scaffold\BaseModel
     */
    public function getModel()
    {
        if (!$this->model_class) {
            return null;
        }
        return make($this->model_class);
    }

    /**
     * 获取当前操作entity的主键
     *
     * @return string
     */
    public function getPk()
    {
        $entity = $this->getEntity();
        return $entity ? $this->getEntity()->getPk() : null;
    }

    public function formComputeConfig($form)
    {
        $compute_map = [];
        $options_map = [];
        foreach ($form as $item) {
            if (isset($item['depend'])) {
                $compute_map[$item['depend']['field']][$item['field']][] = [
                    'when' => [
                        [
                            $item['depend']['field'],
                            is_array($item['depend']['value']) ? 'not_in' : '!=',
                            $item['depend']['value'],
                        ],
                    ],
                    'set' => [
                        'type' => 'hidden',
                    ],
                ];
            }
            if (isset($item['hidden'])) {
                $compute_map[$item['field']][$item['hidden']['field']][] = [
                    'when' => [[$item['field'], '=', $item['hidden']['value']]],
                    'set' => [
                        'type' => 'hidden',
                    ],
                ];
            }
            if (isset($item['compute'])) {
                if (isset($item['compute']['when'])) {
                    foreach ($item['compute']['set'] as &$set) {
                        $set = $this->formComputeSetConvert($set);
                        unset($set);
                    }
                    foreach ($item['compute']['set'] as $key => $detail) {
                        $compute_map[$item['field']][$key][] = [
                            'when' => [array_merge([$item['field']], $item['compute']['when'])],
                            'set' => $detail,
                        ];
                    }
                }
                if (isset($item['compute'][0])) {
                    foreach ($item['compute'] as $each) {
                        foreach ($each['set'] as &$set) {
                            $set = $this->formComputeSetConvert($set);
                            unset($set);
                        }
                        foreach ($each['set'] as $key => $detail) {
                            $compute_map[$item['field']][$key][] = [
                                'when' => [array_merge([$item['field']], $each['when'])],
                                'set' => $detail,
                            ];
                        }
                    }
                }
            }
            if (isset($item['options'])) {
                foreach ($item['options'] as $each) {
                    $options_map[$item['field']][] = [
                        'value' => $each['value'],
                        'label' => $each['label'],
                    ];
                    if (!isset($each['disabled_when'])) {
                        continue;
                    }
                    if (is_string($each['disabled_when'][0])) {
                        $compute_map[$each['disabled_when'][0]][$item['field']][] = [
                            'when' => [$each['disabled_when']],
                            'set' => [
                                'options' => [
                                    [
                                        'value' => $each['value'],
                                        'label' => $each['label'],
                                        'disabled' => true,
                                    ],
                                ],
                            ],
                        ];
                        continue;
                    }
                    foreach ($each['disabled_when'] as $line) {
                        $compute_map[$line[0]][$item['field']][] = [
                            'when' => $each['disabled_when'],
                            'set' => [
                                'options' => [
                                    [
                                        'value' => $each['value'],
                                        'label' => $each['label'],
                                        'disabled' => true,
                                    ],
                                ],
                            ],
                        ];
                    }
                }
            }
        }
        $real_map = [];
        foreach ($compute_map as $change_field => $item) {
            foreach ($item as $affect_field => $parts) {
                $group = [];
                foreach ($parts as $part) {
                    $key = json_encode($part['when']);
                    if (!isset($group[$key])) {
                        $group[$key] = $part['set'];
                    } else {
                        $group[$key] = array_merge_recursive($group[$key], $part['set']);
                    }
                }
                $cell = [];
                foreach ($group as $when => $set) {
                    if (isset($set['options'])) {
                        $set['options'] = array_merge_node($options_map[$affect_field] ?? [], $set['options'], 'value');
                    }
                    $cell[] = [
                        'when' => json_decode($when, true),
                        'set' => $set,
                    ];
                }
                $real_map[$change_field][$affect_field] = $cell;
            }
        }
        return $real_map;
    }

    public function formComputeSetConvert($cell)
    {
        if (isset($cell['rule'])) {
            $validate = $this->validateOptions('input', '', explode('|', $cell['rule']));
            $cell['validate'] = $validate;
            unset($cell['rule']);
        }
        foreach ($cell as $key => $value) {
            if (is_callable($value)) {
                $cell[$key] = call($value, [$cell]);
            }
        }
        return $cell;
    }

    /**
     * 表单配置拉取接口
     *
     * @return array
     */
    public function form()
    {
        $form = $this->formOptionsConvert([], false, false);
        return $this->success($this->formResponse(0, $form));
    }

    public function formResponse($id, $form)
    {
        if (method_exists($this, 'meddleFormRule')) {
            $this->meddleFormRule($id, $form);
        }
        $compute_map = $this->formComputeConfig($form);
        return [
            'form' => $form,
            'compute_map' => (object)$compute_map,
            'form_ui' => (object)($this->options['formUI'] ?? []),
        ];
    }

    /**
     * 表单拉取接口
     *
     * @param int $id 主键值
     *
     * @return array
     */
    public function edit(int $id)
    {
        $record = $this->getEntity()->get($id);
        $history_versions = [];
        $version_enable = $this->getEntity()->isVersionEnable();
        if ($version_enable && $record && method_exists($this, 'getRecordHistory')) {
            $history_versions = $this->getRecordHistory();
        }
        $ver_id = $this->request->input('_ver');
        if ($ver_id && $history_versions) {
            $versions = $history_versions;
            array_change_v2k($versions, 'id');
            $record = isset($versions[$ver_id]) ? $versions[$ver_id]['content'] : $record;
        }
        if (method_exists($this, 'beforeFormResponse')) {
            $this->beforeFormResponse($id, $record);
        }
        $form = $this->formOptionsConvert([], false, true, false, $record);
        return $this->success(array_merge($this->formResponse($id, $form), [
            'version_enable' => $version_enable,
            'version_list' => $history_versions,
        ]));
    }

    /**
     * 获取所有字段, 共列表/表单使用
     */
    public function getFields()
    {
        $form = $this->formOptionsConvert();
        $form = array_filter($form, function ($item) {
            return !($item['virtual_field'] ?? false);
        });
        $fields = array_column($form, 'field');
        $fields = array_map(function ($item) {
            return explode('.', $item)[0];
        }, $fields);
        return array_unique($fields);
    }

    /**
     * 获取列表的搜索项
     */
    public function getListFilters()
    {
        if (empty($this->options['filter'])) {
            return [];
        }
        $form_fields = $this->getFormFieldMap();
        $form_options = $this->options['form'] ?? [];
        $filter_options = [];
        foreach ($this->options['filter'] as $key => $item) {
            $filter_option_key = is_array($item) ? $key : str_replace('%', '', $item);
            $field_extra = explode('|', $filter_option_key);
            $field = $field_extra[0];
            $form_option = [];
            if (isset($form_fields[$field]) && isset($form_options[$form_fields[$field]])) {
                $filter_option_key = $form_fields[$field];
                $form_option = is_array($form_options[$form_fields[$field]]) ? $form_options[$form_fields[$field]] : [];
                if (isset($form_option['rule'])) {
                    unset($form_option['rule']);
                }
            }
            $filter_option = is_array($item) ? $item : [];
            if (!empty($field_extra[1])) {
                $filter_option_key = "{$field}|{$field_extra[1]}";
            }
            $filter_options[$filter_option_key] = array_merge($form_option, $filter_option);
            if (!isset($filter_options[$filter_option_key]['search_type']) && is_string($item)) {
                $search_type = 'eq';
                if (Str::startsWith($item, '%') !== false) {
                    $search_type = 'prefix_like';
                }
                if (Str::endsWith($item, '%') !== false) {
                    $search_type = 'suffix_like';
                }
                if (Str::startsWith($item, '%') !== false && Str::endsWith($item, '%') !== false) {
                    $search_type = 'full_like';
                }
                if (strpos(($filter_options[$filter_option_key]['type'] ?? ''), 'range') !== false) {
                    $search_type = 'between';
                }
                $filter_options[$filter_option_key]['search_type'] = $search_type;
            }
        }
        unset($form_options);
        return $this->formOptionsConvert($filter_options, true, false, true);
    }

    /**
     * 获取列表的表头
     */
    public function getListHeader()
    {
        $form = $this->formOptionsConvert();
        array_change_v2k($form, 'field');
        $table_options = $this->options['table']['columns'] ?? [];
        $headers = [];
        foreach ($table_options as $item) {
            if (is_string($item)) {
                $header = [
                    'title' => $form[$item]['title'] ?? $item,
                    'field' => $form[$item]['field'] ?? $item,
                    'type' => $form[$item]['type'] ?? '',
                    'virtual_field' => $form[$item]['virtual_field'] ?? false,
                    'sortable' => false,
                ];
            } else {
                $header = array_merge(!empty($form[$item['field']]) ? [
                    'type' => $item['type'] ?? $form[$item['field']]['type'],
                    'title' => $form[$item['field']]['title'],
                    'sortable' => $item['sortable'] ?? false,
                    'virtual_field' => $item['virtual_field'] ?? false,
                ] : [], $item);
            }
            if ($form[$header['field']]['options'] ?? false) {
                $options = [];
                foreach ($form[$header['field']]['options'] as $each) {
                    $options[$each['value']] = $each['label'];
                }
                $header['options'] = $options;
            }
            $headers[] = $header;
        }
        if (!$table_options) {
            foreach ($form as $item) {
                $headers[] = [
                    'title' => $item['title'],
                    'field' => $item['field'],
                    'sortable' => $item['sortable'] ?? false,
                    'virtual_field' => $item['virtual_field'] ?? false,
                ];
            }
        }
        return $headers;
    }

    /**
     * 表单配置转换
     *
     * @param array $formOption 表单配置
     * @param bool  $full       是否为全部, false 则会过滤虚拟字段
     * @param bool  $edit       是否为编辑模式, 用于处理 新增或编辑 时, 字段的只读问题
     * @param bool  $filter     是否为filter模式,用于处理id字段的type
     * @param array $default
     * @param int   $depth      深度
     *
     * @return array
     */
    public function formOptionsConvert($formOption = [], $full = false, $edit = true, $filter = false, $default = [], $depth = 0)
    {
        if (!$formOption) {
            $formOption = $this->options['form'] ?? [];
        }
        $form = [];
        foreach ($formOption as $key => $val) {
            $field_extra = explode('|', $key);
            $field = $field_extra[0];
            $title = $field_extra[1] ?? $field_extra[0];
            $biz = [];
            if (is_string($val)) {
                $biz['rule'] = $val;
                $biz['type'] = 'input';
            } else {
                $biz = $val;
            }
            if ($full === false && ($biz['form'] ?? true) === false) {
                continue;
            }
            $rule = $biz['rule'] ?? '';
            $rules = is_array($rule) ? $rule : explode('|', $rule);
            $_form = [
                'title' => $title,
                'field' => $field,
                'type' => $biz['type'] ?? 'input',
                'value' => Arr::get(array_merge($this->request->all(), $default ?: []), $field, $biz['default'] ?? ''),
            ];
            switch ($_form['type']) {
                case 'checkbox':
                case 'cascader':
                    $_form['value'] = array_map('intval', is_array($_form['value']) ? $_form['value'] : (array)$_form['value']);
                    break;
                case 'image':
                    $biz['props']['limit'] = $biz['props']['limit'] ?? 1;
                    break;
                case 'select':
                    if (isset($biz['props']['selectApi']) && $_form['value']) {
                        $biz['options'] = select_options($biz['props']['selectApi'], is_array($_form['value']) ? $_form['value'] : explode(',', $_form['value']));
                    }
                    // fixme sub-form value 不好取, 先默认查一次
                    if (isset($biz['props']['selectApi']) && $depth) {
                        $biz['options'] = select_options($biz['props']['selectApi'], is_array($_form['value']) ? $_form['value'] : explode(',', $_form['value']));
                    }
                    break;
                default:
                    break;
            }
            $validate = $this->validateOptions($_form['type'], $_form['title'], $rules);
            if (isset($biz['children'])) {
                $biz['props']['rules'] = $this->formOptionsConvert($biz['children'], $full, $edit, $filter, Arr::get($field, $default, []), $depth + 1);
                $biz['props']['computeMap'] = (object)$this->formComputeConfig($biz['props']['rules']);
                $biz['props']['repeat'] = $biz['repeat'] ?? false;
                $_form['value'] = is_array($_form['value']) ? $_form['value'] : [];
            }
            if ($validate) {
                $_form['validate'] = $validate;
            }
            if (!$filter && $field == $this->getPk()) {
                $_form['type'] = 'hidden';
            }
            if ($biz['props'] ?? false) {
                $_form['props'] = $biz['props'];
            }
            if ($biz['col'] ?? false) {
                $_form['col'] = $biz['col'];
            }
            if ($biz['info'] ?? false) {
                $_form['info'] = $biz['info'];
            }
            if (isset($biz['depend'])) {
                $_form['depend'] = $biz['depend'];
            }
            if (isset($biz['hidden'])) {
                $_form['hidden'] = $biz['hidden'];
            }
            if (isset($biz['custom'])) {
                $_form['custom'] = (bool)$biz['custom'];
            }
            if (isset($biz['virtual_field'])) {
                $_form['virtual_field'] = (bool)$biz['virtual_field'];
            }
            if (isset($biz['readonly']) && $edit) {
                $_form['props']['disabled'] = (bool)$biz['readonly'];
            }
            if (isset($biz['section'])) {
                $_form['section'] = $biz['section'];
            }
            if (isset($biz['compute'])) {
                $_form['compute'] = $biz['compute'];
            }
            if (isset($biz['search_type'])) {
                $_form['search_type'] = $biz['search_type'];
            }
            if (isset($biz['options']) && is_callable($biz['options'])) {
                $_form['options'] = $biz['options']($field, $default);
            } elseif (($biz['options'] ?? false) && ($biz['type'] != 'cascader')) {
                $value_label = [];
                $first = current($biz['options']);
                if (!isset($first['value'])) {
                    foreach ($biz['options'] as $value => $label) {
                        $value_label[] = is_array($label) ? $label : [
                            'value' => $value,
                            'label' => $label,
                        ];
                    }
                } else {
                    $value_label = $biz['options'];
                }
                $_form['options'] = $value_label;
            }
            if ($filter
                && in_array($_form['type'], [
                    'radio',
                    'select',
                    'checkbox',
                ])
                && isset($_form['options'])) {
                $_form['type'] = 'select';
                unset($_form['value']);
                $options_labels = array_column($_form['options'], 'label');
                if (!isset($_form['props']['selectApi']) && !in_array('全部', $options_labels)) {
                    array_unshift($_form['options'], [
                        'value' => '',
                        'label' => '全部',
                    ]);
                }
            }
            if (isset($biz['copy_show'])) {
                $_form['copy_show'] = $biz['copy_show'];
            }
            if (isset($biz['render']) && is_callable($biz['render'])) {
                $biz['render']($field, $_form);
            }
            if (isset($biz['filterConvert'])) {
                $_form['filterConvert'] = $biz['filterConvert'];
            }
            $form[] = $_form;
        }
        return $form;
    }

    /**
     * 获取表单的约束
     */
    public function getFormRules($options = null)
    {
        $formOptions = $options ? $options : ($this->options['form'] ?? []);
        $rules = [];
        foreach ($formOptions as $key => $val) {
            if (is_array($val) && ($val['form'] ?? true) === false) {
                continue;
            }
            if (is_string($val)) {
                $rules[$key] = $val;
                continue;
            }
            if (is_array($val) && ($val['rule'] ?? false)) {
                $rules[$key] = $val['rule'];
            } else {
                $rules[$key] = '';
            }
            if (isset($val['children']) && is_array($val['children'])) {
                $rules[$key] = [
                    'children' => [
                        'rules' => $this->getFormRules($val['children']),
                        'repeat' => $val['repeat'] ?? false,
                    ],
                ];
            }
        }
        return $rules;
    }

    public function save()
    {
        $rules = $this->getFormRules();
        $entity = $this->getEntity();
        $pk = $entity->getPk();
        $data_source = $this->request->all();
        $pk_val = $data_source[$pk] ?? null;
        foreach ($rules as &$val) {
            $rule_parts = is_array($val) ? $val : explode('|', $val);
            foreach ($rule_parts as &$rule) {
                if ($pk_val && is_string($rule) && Str::startsWith($rule, 'unique')) {
                    // unique rule without itself in update
                    $rule .= ',' . $pk_val . '_to_ignore';
                }
                unset($rule);
            }
            $val = array_filter($rule_parts);
            unset($val);
        }
        [
            $data,
            $errors,
        ] = $this->validation->check($rules, $data_source, $this);
        if ($errors) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, implode(PHP_EOL, $errors));
        }
        if ($pk_val === null) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '表单配置错误, 请联系管理员');
        }
        if (method_exists($this, 'beforeSave')) {
            try {
                $this->beforeSave($pk_val, $data);
            } catch (\Exception $e) {
                return $this->fail($e->getCode() ?? ErrorCode::FAIL, $e->getMessage());
            }
        }
        try {
            if ($pk_val) {
                $saved = $entity->set($pk_val, $data);
            } else {
                unset($data[$pk]);
                $saved = $entity->create($data);
                $pk_val = $saved;
            }
            if ($saved) {
                if (method_exists($this, 'afterSave')) {
                    $this->afterSave($pk_val, $data, $entity);
                }
                return $this->success();
            } else {
                return $this->fail(ErrorCode::CODE_ERR_SERVER);
            }
        } catch (\Exception $e) {
            return $this->fail(is_int($e->getCode()) ? $e->getCode() : ErrorCode::CODE_ERR_SERVER, $e->getMessage());
        }
    }

    /**
     * 删除接口
     */
    public function delete()
    {
        $entity = $this->getEntity();
        $pk = $entity->getPk();
        $pk_val = $this->request->input($pk);
        if (!$pk_val) {
            return $this->fail(ErrorCode::CODE_ERR_DENY);
        }
        try {
            if (method_exists($this, 'beforeDelete')) {
                $this->beforeDelete($pk_val);
            }
            $deleted = $entity->delete($pk_val);
            if (method_exists($this, 'afterDelete')) {
                $this->afterDelete($pk_val, $deleted);
            }
            return $deleted ? $this->success() : $this->fail(ErrorCode::CODE_ERR_SERVER, '删除失败');
        } catch (\Exception $e) {
            return $this->fail(is_int($e->getCode()) ? $e->getCode() : ErrorCode::CODE_ERR_SERVER, $e->getMessage());
        }
    }

    public function batchDelete()
    {
        $entity = $this->getEntity();
        $pk = $entity->getPk();
        $selected = $this->request->input('selected');
        $pks = array_filter(array_column($selected, $pk));
        if (!$pks) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM);
        }
        try {
            if (method_exists($this, 'beforeDelete')) {
                $this->beforeDelete($pks);
            }
            $deleted = $entity->delete($pks);
            if (method_exists($this, 'afterDelete')) {
                $this->afterDelete($pks, $deleted);
            }
            return $deleted ? $this->success() : $this->fail(ErrorCode::CODE_ERR_SERVER, '删除失败');
        } catch (\Exception $e) {
            return $this->fail(is_int($e->getCode()) ? $e->getCode() : ErrorCode::CODE_ERR_SERVER, $e->getMessage());
        }
    }

    /**
     * 表单中编辑的保存接口
     *
     * @param int $id
     *
     * @return array
     */
    public function rowChange($id)
    {
        $rules = $this->getFormRules();
        $up = $this->request->all();
        $up_fields = array_keys($up);
        foreach ($rules as $key => $val) {
            $field_extra = explode('|', $key);
            $field = $field_extra[0];
            if (!in_array($field, $up_fields)) {
                unset($rules[$key]);
            }
        }
        [
            $data,
            $errors,
        ] = $this->validation->check($rules, $this->request->all());
        if ($errors) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, implode(PHP_EOL, $errors));
        }
        if (method_exists($this, 'beforeRowChangeSave')) {
            try {
                $this->beforeRowChangeSave($id, $data);
            } catch (\Exception $e) {
                return $this->fail($e->getCode() ?? ErrorCode::FAIL, $e->getMessage());
            }
        }
        $saved = $this->getEntity()->set($id, $data);
        return $saved ? $this->success() : $this->fail(ErrorCode::CODE_ERR_SERVER);
    }

    /**
     * 脚手架通用抛出异常
     *
     * @param string     $message
     * @param int        $code
     * @param \Throwable $previous
     *
     * @return void
     * @throws \Exception
     */
    public function exception($message = "", $code = ErrorCode::CODE_ERR_SYSTEM, \Throwable $previous = null)
    {
        throw new \Exception($message, $code, $previous);
    }

    /**
     * laravel-validation -> async-validator
     * 脚手架字段约束转换
     *
     * @param string $type  字段类型
     * @param string $title 字段中文名
     * @param array  $rules 字段约束
     *
     * @return array
     */
    public function validateOptions($type, $title, $rules)
    {
        $validates = [];
        foreach ($rules as $item) {
            $parts = explode(':', $item);
            $rule = array_shift($parts);
            switch ($rule) {
                case 'required':
                    $validates[] = [
                        'required' => true,
                        'message' => '请输入' . $title,
                        //'type' => $type,
                        'trigger' => 'blur',
                    ];
                    break;
            }
        }
        return $validates;
    }

    /**
     * 从form options中提取field对应的form options的key
     *
     * @return array
     */
    public function getFormFieldMap()
    {
        $form_options = $this->options['form'] ?? [];
        if (empty($form_options)) {
            return [];
        }
        return collect(array_keys($form_options))->mapWithKeys(function ($item) {
            $field_extra = explode('|', $item);
            return [$field_extra[0] => $item];
        })->toArray();
    }

    /**
     * form表单中二次确认方法
     *
     * @param string   $confirm_msg           二次确认的提示文案
     * @param callable $need_confirm_callable 当何种情况下需要二次确认
     *
     * @return mixed
     */
    public function confirm($confirm_msg, $need_confirm_callable)
    {
        if ($need_confirm_callable() && !$this->request->input('_repeat')) {
            $this->exception($confirm_msg, 40012);
        }
    }

    /**
     * select options 接口
     */
    public function act()
    {
        $attr = ['select' => ['id as value', 'name as label']];
        $model = $this->getEntity();
        $options = $model->search($attr);
        return $this->success($options);
    }

    //public function riskCheck()
    //{
    //    if (!empty($this->options['risk']) && !empty($this->options['riskAction'])) {
    //        $check_param = [
    //            'riskAction' => $this->options['riskAction'],
    //            'fields' => $this->request->all(),
    //        ];
    //        $confirm_message = (new RiskCheck)->check($check_param);
    //        if ($confirm_message) {
    //            throw new \Exception($confirm_message, 40012);
    //        }
    //    }
    //}

    /**
     * 拉取模块提示接口
     * notices: "测试一下",
     * notices: [
     *      {
     *          "message": "测试二下"
     *      }
     * ]
     */
    public function notice()
    {
        $notices = $this->options['notices'] ?? [];
        if (empty($notices)) {
            return $this->success();
        }
        $filters = [];
        if ($filter_options = $this->getListFilters()) {
            array_change_v2k($filter_options, 'field');
            $filters = array_filter($this->request->inputs(array_keys($filter_options)), function ($item) {
                return !in_array($item, [null, '']);
            });
        }
        $list = [];
        if (is_array($notices)) {
            foreach ($notices as $notice) {
                if (isset($notice['when'])) {
                    if (!is_callable($notice['when'])) {
                        continue;
                    }
                    $ok = $notice['when']($filters);
                    if (!$ok) {
                        continue;
                    }
                    unset($notice['when']);
                }
                $list[] = $notice;
            }
        } else {
            $list[] = [
                'message' => $notices,
            ];
        }
        return $this->success(compact('list'));
    }

    /**
     * 获取当前操作entity对象
     *
     * @return \HyperfAdmin\BaseUtils\Scaffold\Entity\EntityInterface
     */
    public function getEntity()
    {
        if ($this->entity_class) {
            return make($this->entity_class);
        }
        if ($this->model_class && make($this->model_class) instanceof BaseModel) {
            return new class ($this->model_class) extends MysqlEntityAbstract{
            };
        }
        if ($this->model_class && make($this->model_class) instanceof EsBaseModel) {
            return new class ($this->model_class) extends EsEntityAbstract{
            };
        }
        return null;
    }

    /**
     * @param $field
     *
     * @return array
     */
    public function options($field)
    {
        $const = [];
        if ($model = $this->model_class ?? '') {
            $const = constant($model . '::' . strtoupper($field)) ?? [];
        }
        $options = [];
        foreach ($const as $k => $v) {
            $options[] = [
                'id' => $k,
                'name' => $v,
            ];
        }
        return $this->success($options);
    }


    /**
     * 新版本检查
     *
     * @param int $id
     * @param int $last_ver_id
     *
     * @return array
     */
    public function newVersion(int $id, int $last_ver_id)
    {
        $last = $this->getEntity()->lastVersion();
        if (!$last || $last->id == $last_ver_id) {
            return $this->success(['has_new_version' => false]);
        }
        if ($last->user_id == $this->user()['id']) {
            return $this->success(['has_new_version' => false]);
        }
        return $this->success([
            'has_new_version' => true,
            'message' => sprintf("%s有新的数据产生, 请刷新页面获取最新数据", $last->created_at),
        ]);
    }
}
