<?php
namespace HyperfAdmin\Admin\Controller;

use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Utils\Str;
use HyperfAdmin\Admin\Model\CommonConfig;
use HyperfAdmin\Admin\Model\FrontRoutes;
use HyperfAdmin\Admin\Model\RoleMenu;
use HyperfAdmin\Admin\Service\Menu;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;

class MenuController extends AdminAbstractController
{
    protected $model_class = FrontRoutes::class;

    public function scaffoldOptions()
    {
        return [
            'exportAble' => false,
            'createAble' => false,
            'where' => [
                'pid' => 0,
            ],
            'formUI' => [
                'form' => [
                    'size' => 'mini',
                ],
            ],
            'form' => [
                'id|#' => 'int',
                'module|#' => [
                    'type' => 'hidden',
                    'render' => function ($field, &$data) {
                        $data['value'] = request()->input('module', $data['value'] ?? 'system');
                    },
                ],
                'type|菜单类型' => [
                    'type' => 'radio',
                    'default' => 1,
                    'options' => ['目录', '菜单', '权限'],
                    'compute' => [
                        [
                            'when' => ['in', [0, 2]],
                            'set' => [
                                'is_scaffold' => [
                                    'type' => 'hidden',
                                ],
                                'other_menu' => [
                                    'type' => 'hidden',
                                ],
                                'path' => [
                                    'type' => 'hidden',
                                ],
                                'page_type' => [
                                    'type' => 'hidden',
                                ],
                            ],
                        ],
                        [
                            'when' => ['=', 1],
                            'set' => [
                                'path' => [
                                    'rule' => 'requir渲染方式ed',
                                ],
                                'label' => [
                                    'title' => '菜单标题',
                                    'col' => [
                                        'span' => 12,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'when' => ['=', 2],
                            'set' => [
                                'icon' => [
                                    'type' => 'hidden',
                                ],
                                'is_menu' => [
                                    'type' => 'hidden',
                                ],
                                'label' => [
                                    'title' => '权限名称',
                                    'col' => [
                                        'span' => 24,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'when' => ['=', 0],
                            'set' => [
                                'permission' => [
                                    'type' => 'hidden',
                                ],
                                'label' => [
                                    'title' => '菜单标题',
                                    'col' => [
                                        'span' => 12,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'icon|菜单图标' => [
                    'type' => 'icon-select',
                    'options' => [
                        'example' => 'example',
                    ],
                    'col' => [
                        'span' => 12,
                    ],
                ],
                'sort|菜单排序' => [
                    'type' => 'number',
                    'default' => 99,
                    'col' => [
                        'span' => 12,
                    ],
                ],
                'label|菜单标题' => [
                    'rule' => 'required|string|max:10',
                    'col' => [
                        'span' => 12,
                    ],
                ],
                'path|路由地址' => [
                    'rule' => 'string|max:100',
                    'default' => '',
                    'col' => [
                        'span' => 12,
                    ],
                ],
                'is_menu|菜单可见' => [
                    'type' => 'radio',
                    'options' => [
                        0 => '否',
                        1 => '是',
                    ],
                    'default' => 1,
                    'col' => [
                        'span' => 12,
                    ],
                ],
                'is_scaffold|渲染方式' => [
                    'type' => 'radio',
                    'options' => [
                        1 => '脚手架',
                        0 => '自定义',
                        2 => '配置化脚手架',
                    ],
                    'default' => 1,
                    'col' => [
                        'span' => 12,
                    ],
                    'compute' => [
                        'when' => ['=', 0],
                        'set' => [
                            'view' => [
                                'rule' => 'required',
                            ],
                        ],
                    ],
                ],
                'config|脚手架配置' => [
                    "type" => 'json',
                    'depend' => [
                        'field' => 'is_scaffold',
                        'value' => 2
                    ]
                ],
                'view|组件路径' => [
                    'rule' => 'string|max:50',
                    'default' => '',
                    'depend' => [
                        'field' => 'is_scaffold',
                        'value' => 0,
                    ],
                ],
                'scaffold_action|预置权限' => [
                    'type' => 'checkbox',
                    'virtual_field' => true,
                    'options' => function ($field, $data) {
                        $scaffold_permissions = config('scaffold_permissions');
                        $options = [];
                        foreach ($scaffold_permissions as $key => $permission) {
                            $options[] = [
                                'value' => $key,
                                'label' => $permission['label'],
                            ];
                        }

                        return $options;
                    },
                    'info' => '新增和编辑会创建/form或/:id的前端路由',
                    'depend' => [
                        'field' => 'is_scaffold',
                        'value' => 1,
                    ],
                ],
                'permission|权限标识' => [
                    'type' => 'select',
                    'default' => [],
                    'props' => [
                        'multiple' => true,
                        'selectApi' => '/system/routes?module={module}'
                    ],
                ],
                'pid|上级类目' => [
                    'rule' => 'array',
                    'type' => 'cascader',
                    'default' => [],
                    'options' => function ($field, $data) {
                        $module = request()->input('module', $data['module'] ?? 'system');

                        return (new Menu())->tree([
                            'module' => $module,
                            'type' => [0, 1],
                        ]);
                    },
                    'props' => [
                        'style' => 'width: 100%;',
                        'clearable' => true,
                        'props' => [
                            'checkStrictly' => true,
                        ],
                    ],
                ],
                'roles|分配角色' => [
                    'rule' => 'array',
                    'type' => 'cascader',
                    'virtual_field' => true,
                    'props' => [
                        'style' => 'width: 100%;',
                        'props' => [
                            'multiple' => true,
                            'leaf' => 'leaf',
                            'emitPath' => false,
                            'checkStrictly' => true,
                        ],
                    ],
                    'render' => function ($field, &$data) {
                        $id = (int)$this->request->route('id', 0);
                        $data['value'] = $this->permission_service->getMenuRoleIds($id);
                        $data['options'] = $this->permission_service->getRoleTree();
                    },
                ],
            ],
            'table' => [
                'is_tree' => true,
                'tabs' => function() {
                    $conf = \HyperfAdmin\Admin\Service\CommonConfig::getValByName('website_config');
                    $system_module = $conf['system_module'] ?? [];
                    return array_map(function ($item) {
                        return [
                            'label' => $item['label'],
                            'value' => $item['name'],
                            'icon' => $item['icon']
                        ];
                    }, $system_module);
                },
                'rowActions' => [
                    [
                        'text' => '编辑',
                        'type' => 'form',
                        'target' => '/menu/{id}',
                        'formUi' => [
                            'form' => [
                                'labelWidth' => '80px',
                                'size' => 'mini',
                            ],
                        ],
                        'props' => [
                            'type' => 'primary',
                        ],
                    ],
                    [
                        'text' => '加子菜单',
                        'type' => 'form',
                        'target' => '/menu/form?pid[]={id}&module={module}',
                        'formUi' => [
                            'form' => [
                                'labelWidth' => '80px',
                                'size' => 'mini',
                            ],
                        ],
                        'props' => [
                            'type' => 'success',
                        ],
                    ],
                    [
                        'text' => '删除',
                        'type' => 'api',
                        'target' => '/menu/delete',
                        'props' => [
                            'type' => 'danger',
                        ],
                    ],
                ],
                'topActions' => [
                    [
                        'text' => '清除权限缓存',
                        'type' => 'api',
                        'target' => '/menu/permission/clear',
                        'props' => [
                            'icon' => 'el-icon-delete',
                            'type' => 'warning',
                        ],
                    ],
                    [
                        'text' => '公共资源',
                        'type' => 'jump',
                        'target' => '/cconf/cconf_permissions',
                        'props' => [
                            'icon' => 'el-icon-setting',
                            'type' => 'primary',
                        ],
                    ],
                    [
                        'text' => '新建',
                        'type' => 'form',
                        'target' => '/menu/form?module={tab_id}',
                        'formUi' => [
                            'form' => [
                                'labelWidth' => '80px',
                                'size' => 'mini',
                            ],
                        ],
                        'props' => [
                            'icon' => 'el-icon-plus',
                            'type' => 'success',
                        ],
                    ],
                ],
                'columns' => [
                    ['field' => 'id', 'hidden' => true],
                    ['field' => 'pid', 'hidden' => true],
                    ['field' => 'module', 'hidden' => true],
                    [
                        'field' => 'label',
                        'width' => '250px',
                    ],
                    [
                        'field' => 'is_menu',
                        'enum' => [
                            0 => 'info',
                            1 => 'success',
                        ],
                        'width' => '80px;',
                    ],
                    [
                        'field' => 'icon',
                        'type' => 'icon',
                        'width' => '80px;',
                    ],
                    'path',
                    'permission',
                    [
                        'field' => 'sort',
                        'edit' => true,
                        'width' => '170px;',
                    ],
                ],
            ],
            'order_by' => 'sort desc',
        ];
    }

    protected function beforeFormResponse($id, &$record)
    {
        if (in_array($record['type'], [
                1,
                2,
            ])
            && !empty($record['permission'])) {
            $record['permission'] = array_map(function ($item) use ($record) {
                if (!Str::contains($item, '::')) {
                    $http_method = FrontRoutes::$http_methods[$record['http_method']];

                    return "{$http_method}::{$item}";
                }

                return $item;
            }, array_filter(explode(',', $record['permission'])));
        }
        $scaffold_action = json_decode($record['scaffold_action'], true);
        $record['scaffold_action'] = $scaffold_action ? array_keys($scaffold_action) : [];
        $record['pid'] = (new Menu())->getPathNodeIds($id);
    }

    protected function beforeSave($id, &$data)
    {
        if ($data['type'] == 1) {
            if ($data['path'] == '#' || $data['path'] == '') {
                $this->exception('菜单路由地址不能为空或"#"', ErrorCode::CODE_ERR_PARAM);
            }
            $paths = array_filter(explode('/', $data['path']));
            if (count($paths) > 5) {
                $this->exception('路由地址层级过深>5，请设置精简一些', ErrorCode::CODE_ERR_PARAM);
            }
        } else {
            $data['path'] = '#';
        }
        $data['is_menu'] = $data['type'] == 2 ? 0 : $data['is_menu'];
        if ($data['permission']) {
            $data['permission'] = implode(',', $data['permission'] ?? []);
        }
        $pid = array_pop($data['pid']);
        if ($pid == $id) {
            $pid = array_pop($data['pid']);
        }
        $data['pid'] = (int)$pid;
        if ($data['type'] > 1) {
            $parent_info = $this->getModel()->find($data['pid']);
            if (!$parent_info || $parent_info['type'] != 1) {
                $this->exception('菜单类型为权限时请选择一个上级类目', ErrorCode::CODE_ERR_PARAM);
            }
        }
        $data['status'] = YES;
    }

    protected function afterSave($pk_val, $data, $entity)
    {
        // 更新预置的脚手架权限
        $scaffold_action = json_decode($entity->scaffold_action, true);
        $action_keys = $scaffold_action ? array_keys($scaffold_action) : [];
        $need_del_ids = $scaffold_action ? array_values($scaffold_action) : [];
        $router_ids = [];
        if (!empty($data['scaffold_action'])) {
            $need_del_ids = collect($scaffold_action)->except($data['scaffold_action'])->values()->toArray();
            $scaffold_action = collect($scaffold_action)->only($data['scaffold_action'])->toArray();
            $paths = array_filter(explode('/', $data['path']));
            array_pop($paths);
            $prefix = implode('/', $paths);
            foreach ($data['scaffold_action'] as $k => $action) {
                if (in_array($action, $action_keys)) {
                    continue;
                }
                $action_conf = config("scaffold_permissions.{$action}");
                $menu = [
                    'pid' => $pk_val,
                    'label' => $action_conf['label'],
                    'path' => !empty($action_conf['path']) ? "/{$prefix}" . $action_conf['path'] : '',
                    'permission' => str_replace('/*/', "/{$prefix}/", $action_conf['permission']),
                    'is_scaffold' => $action_conf['type'] == 1 ? 1 : 0,
                    'module' => $data['module'],
                    'type' => $action_conf['type'],
                    'sort' => 99 - $k,
                    'status' => 1,
                ];
                $model = make(FrontRoutes::class);
                $model->fill($menu)->save();
                $scaffold_action[$action] = $model->id;
                $router_ids[] = $model->id;
            }
        } else {
            $scaffold_action = '';
        }
        $entity->scaffold_action = json_encode($scaffold_action);
        // todo entity
        //$entity->save();
        // 删除路由
        if (!empty($need_del_ids)) {
            $this->getModel()->destroy($need_del_ids);
            make(RoleMenu::class)->where2query(['router_id' => $need_del_ids])->delete();
        }
        // 分配角色
        if (!empty($data['roles'])) {
            $role_menus = [];
            $router_ids[] = $pk_val;
            foreach ($data['roles'] as $role_id) {
                foreach ($router_ids as $router_id) {
                    $role_menus[] = [
                        'router_id' => $router_id,
                        'role_id' => $role_id,
                    ];
                }
            }
            make(RoleMenu::class)->insertOnDuplicateKey($role_menus);
        } else {
            // 删除当前菜单已分配的角色
            make(RoleMenu::class)->where2query(['router_id' => $pk_val])->delete();
        }
        // 清除缓存
        $this->permission_service->getPermissionCacheKey(0, true);
    }

    protected function afterDelete($pk_val, $deleted)
    {
        if ($deleted) {
            // 删除子菜单
            $sub_ids = $this->getModel()->where2query(['pid' => $pk_val])->select(['id'])->get()->toArray();
            if ($sub_ids) {
                $sub_ids = array_column($sub_ids, 'id');
                $this->afterDelete($sub_ids, $deleted);
            }
            if (is_array($pk_val)) {
                make(FrontRoutes::class)->where2query(['id' => $pk_val])->delete();
            }
            make(RoleMenu::class)->where2query(['router_id' => $pk_val])->delete();
        }
    }

    public function clearPermissionCache()
    {
        $this->permission_service->getPermissionCacheKey(0, true);

        return $this->success();
    }

    public function getOpenApis()
    {
        $field = $this->request->input('field', 'open_api');
        $conf = CommonConfig::query()->where([
                'namespace' => 'system',
                'name' => 'permissions',
            ])->value('value')[$field] ?? [];
        $router = container(DispatcherFactory::class)->getRouter('http');
        $data = $router->getData();
        $options = [];
        foreach ($data as $routes_data) {
            foreach ($routes_data as $http_method => $routes) {
                $route_list = [];
                if (isset($routes[0]['routeMap'])) {
                    foreach ($routes as $map) {
                        array_push($route_list, ...$map['routeMap']);
                    }
                } else {
                    $route_list = $routes;
                }
                foreach ($route_list as $route => $v) {
                    $route = is_string($route) ? rtrim($route) : rtrim($v[0]->route);
                    $route_key = "$http_method::{$route}";
                    if (in_array($route_key, $conf)) {
                        continue;
                    }
                    // 过滤掉脚手架页面配置方法
                    $callback = is_array($v) ? ($v[0]->callback) : $v->callback;
                    if (!is_array($callback)) {
                        continue;
                    }
                    [$controller, $action] = $callback;
                    if (empty($action) || in_array($action, $this->permission_service->scaffold_actions)) {
                        continue;
                    }
                    $options[] = [
                        'id' => $route_key,
                        'controller' => $controller,
                        'action' => $action,
                        'http_method' => $http_method,
                    ];
                }
            }
        }
        $right_options = [];
        foreach ($conf as $route) {
            [$http_method, $uri] = explode("::", $route, 2);
            $dispatcher = container(DispatcherFactory::class)->getDispatcher('http');
            $route_info = $dispatcher->dispatch($http_method, $uri);
            if (!empty($route_info[1]->callback[0])) {
                $right_options[] = [
                    'id' => $route,
                    'controller' => $route_info[1]->callback[0],
                    'action' => $route_info[1]->callback[1],
                    'http_method' => $http_method,
                ];
            }
        }

        return $this->success([
            'left' => $options,
            'right' => $right_options,
        ]);
    }

    public function beforeListQuery(&$where)
    {
        $where['module'] = $this->request->input('tab_id', 'default');
    }
}
