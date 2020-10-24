<?php
namespace HyperfAdmin\Admin\Controller;

use HyperfAdmin\Admin\Model\Role;
use HyperfAdmin\Admin\Model\RoleMenu;
use HyperfAdmin\Admin\Model\UserRole;

class RoleController extends AdminAbstractController
{
    protected $model_class = Role::class;

    public function scaffoldOptions()
    {
        return [
            'createAble' => true,
            'deleteAble' => true,
            'importAble' => true,
            'filter' => ['name'],
            'where' => [
                'pid' => 0,
            ],
            'form' => [
                'id' => 'int',
                'name|名称' => [
                    'rule' => 'required|max:20',
                    'type' => 'input',
                    'props' => [
                        'size' => 'small',
                        'maxlength' => 20,
                    ],
                ],
                'pid|上级角色' => [
                    'rule' => 'int',
                    'type' => 'select',
                    'info' => '没有上级角色则为一级角色',
                    'default' => 0,
                    'props' => [
                        'multipleLimit' => 1,
                    ],
                    'options' => function ($field, &$data) {
                        $options = $this->permission_service->getAllRoleList(['pid' => 0], [
                            'id as value',
                            'name as label',
                        ]);
                        array_unshift($options, ['value' => 0, 'label' => '无']);

                        return $options;
                    },
                ],
                'sort|排序' => [
                    'rule' => 'int',
                    'type' => 'number',
                    'default' => 0,
                ],
                'permissions|权限设置' => [
                    'rule' => 'Array',
                    'type' => 'el-cascader-panel',
                    'virtual_field' => true,
                    'props' => [
                        'style' => 'height:500px;',
                        'props' => [
                            'multiple' => true,
                            'leaf' => 'leaf',
                            'checkStrictly' => false,
                        ],
                    ],
                    'render' => function ($field, &$data) {
                        $id = (int)$this->request->route('id', 0);
                        [
                            $data['value'],
                            $data['props']['options'],
                        ] = $this->permission_service->getPermissionOptions($id);
                    },
                ],
                'user_ids|授权用户' => [
                    'type' => 'select',
                    'props' => [
                        'multiple' => true,
                        'selectApi' => '/user/act',
                        'remote' => true,
                    ],
                    'virtual_field' => true,
                    'render' => function ($field, &$data) {
                        $id = (int)$this->request->route('id', 0);
                        $data['value'] = $this->permission_service->getRoleUserIds($id);
                        if (!empty($data['value'])) {
                            $data['options'] = select_options($data['props']['selectApi'], $data['value']);
                        }
                    },
                ],
            ],
            'table' => [
                'columns' => [
                    ['field' => 'id', 'hidden' => true],
                    ['field' => 'pid', 'hidden' => true],
                    'name',
                    [
                        'field' => 'sort',
                        'edit' => true,
                    ],
                ],
                'rowActions' => [
                    [
                        'action' => '/role/{id}',
                        'text' => '编辑',
                    ],
                    [
                        'action' => 'api',
                        'api' => '/role/delete',
                        'text' => '删除',
                        'type' => 'danger',
                    ],
                ],
            ],
            'order_by' => 'pid asc, sort desc',
        ];
    }

    protected function beforeListResponse(&$list)
    {
        $ids = array_column($list, 'id');
        $children = $this->getModel()->where2query(['pid' => $ids])->get()->toArray();
        $list = array_merge($list, $children);
        $list = generate_tree($list);
    }

    protected function beforeSave($pk_val, &$data)
    {
        $data['permissions'] = $data['permissions'] ? json_encode($data['permissions']) : '';
        unset($data);
    }

    protected function afterSave($pk_val, $data)
    {
        $data['permissions'] = json_decode($data['permissions'], true);
        if (empty($data['permissions'])) {
            return true;
        }
        // 1、删除角色拥有的菜单
        $id = $data['id'] ?? 0;
        if ((int)$id == $pk_val) {
            // 删除角色关联的菜单
            RoleMenu::where('role_id', $pk_val)->delete();
            // 删除角色关联的用户
            $user_ids = $this->permission_service->getRoleUserIds($pk_val);
            if (!empty($user_ids)) {
                make(UserRole::class)->where2query([
                    'role_id' => $pk_val,
                    'user_id' => array_values(array_diff($user_ids, $data['user_ids'] ?? [])),
                ])->delete();
            }
            // 清除缓存
            $this->permission_service->getPermissionCacheKey(0, true);
        }
        $menu_ids = [];
        foreach ($data['permissions'] as $permissions) {
            unset($permissions[0]);
            $menu_ids = array_merge($menu_ids, $permissions);
        }
        // 2、保存角色新分配的菜单
        $role_menus = [];
        $menu_ids = array_unique($menu_ids);
        foreach ($menu_ids as $menu_id) {
            $role_menus[] = [
                'role_id' => $pk_val,
                'router_id' => (int)$menu_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        if (!empty($role_menus)) {
            RoleMenu::insertOnDuplicateKey($role_menus, [
                'role_id',
                'router_id',
            ]);
        }
        // 3、保存角色关联的用户
        if (!empty($data['user_ids'])) {
            $user_role_ids = [];
            foreach ($data['user_ids'] as $user_id) {
                $user_role_ids[] = [
                    'role_id' => $pk_val,
                    'user_id' => (int)$user_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            if (!empty($user_role_ids)) {
                UserRole::insertOnDuplicateKey($user_role_ids, [
                    'role_id',
                    'user_id',
                ]);
            }
        }

        return true;
    }
}
