<?php
namespace HyperfAdmin\Admin\Controller;

use Carbon\Carbon;
use HyperfAdmin\Admin\Model\ExportTasks;
use HyperfAdmin\Admin\Model\User;
use HyperfAdmin\Admin\Model\UserRole;
use HyperfAdmin\Admin\Service\ExportService;
use HyperfAdmin\Admin\Service\Menu;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\JWT;

class UserController extends AdminAbstractController
{
    protected $model_class = User::class;

    public $open_resources = ['login','logout'];

    public function scaffoldOptions()
    {
        return [
            'createAble' => true,
            'deleteAble' => true,
            'defaultList' => true,
            'filter' => ['realname%', 'username%', 'created_at'],
            'form' => [
                'id' => 'int',
                'username|登录账号' => [
                    'rule' => 'required',
                    'readonly' => true,
                ],
                'avatar|头像' => [
                    'type' => 'image',
                    'rule' => 'string',
                ],
                'realname|昵称' => '',
                'mobile|手机' => '',
                'email|邮箱' => 'email',
                'sign|签名' => '',
                'pwd|密码' => [
                    'virtual_field' => true,
                    'default' => '',
                    'props' => [
                        'size' => 'small',
                        'maxlength' => 20,
                    ],
                    'info' => '若设置, 将会更新用户密码'
                ],
                'status|状态' => [
                    'rule' => 'required',
                    'type' => 'radio',
                    'options' => User::$status,
                    'default' => 0,
                ],
                'is_admin|类型' => [
                    'rule' => 'int',
                    'type' => 'radio',
                    'options' => [
                        NO => '普通管理员',
                        YES => '超级管理员',
                    ],
                    'info' => '普通管理员需要分配角色才能访问角色对应的资源；超级管理员可以访问全部资源',
                    'default' => NO,
                    'render' => function ($field, &$rule) {
                        if (!$this->auth_service->isSupperAdmin()) {
                            $rule['type'] = 'hidden';
                        }
                    },
                ],
                'role_ids|角色' => [
                    'rule' => 'array',
                    'type' => 'el-cascader-panel',
                    'virtual_field' => true,
                    'props' => [
                        'props' => [
                            'multiple' => true,
                            'leaf' => 'leaf',
                            'emitPath' => false,
                            'checkStrictly' => true,
                        ],
                    ],
                    'render' => function ($field, &$data) {
                        $id = (int)$this->request->route('id', 0);
                        $data['value'] = $this->permission_service->getUserRoleIds($id);
                        $data['props']['options'] = $this->permission_service->getRoleTree();
                    },
                ],
                'created_at|创建时间' => [
                    'form' => false,
                    'type' => 'date_range',
                ],
            ],
            'hasOne' => function ($field, $row) {
                return 'hyperf_admin.'.env('HYPERF_ADMIN_DB_NAME').'.user_role:user_id,role_id';
            },
            'table' => [
                'columns' => [
                    'id',
                    'realname',
                    'username',
                    [
                        'field' => 'mobile',
                        'render' => function ($field, $row) {
                            return data_desensitization($field, 3, 4);
                        },
                    ],
                    ['field' => 'avatar', 'render' => 'avatarRender'],
                    'email',
                    [
                        'field' => 'status',
                        'enum' => [
                            User::USER_DISABLE => 'info',
                            User::USER_ENABLE => 'success',
                        ],
                    ],
                    [
                        'field' => 'role_id',
                        'title' => '权限',
                        'virtual_field' => true,
                    ],
                ],
                'rowActions' => [
                    ['action' => '/user/{id}', 'text' => '编辑',],
                ],
            ],
        ];
    }

    public function menu()
    {
        $module = $this->request->input('module', 'default');
        $user = auth()->user();
        $base_path = BASE_PATH . '/runtime/menu/';
        $cache_key = $this->permission_service->getPermissionCacheKey($user['id']);
        $cache_file = "{$base_path}{$cache_key}/{$module}.menu.{$user['id']}.cache";
        $menu_list = file_exists($cache_file) ? require $cache_file : [];
        if (empty($menu_list)) {
            $where = [
                'module' => $module,
                'type' => [0, 1],
            ];
            if (!$this->auth_service->isSupperAdmin()) {
                $user_role_ids = $this->permission_service->getUserRoleIds($user['id']);
                $where['id'] = $this->permission_service->getRoleMenuIds($user_role_ids);
            }
            $menu_list = (new Menu())->tree($where, [
                'id',
                'pid',
                'label as menu_name',
                'is_menu as hidden',
                'is_scaffold as scaffold',
                'path as url',
                'view',
                'icon',
            ], 'id');
            if (!empty($menu_list)) {
                if (file_exists($base_path)) {
                    rmdir_recursive($base_path);
                }
                mkdir($base_path . $cache_key, 0755, true);
                file_put_contents($cache_file, '<?php return ' . var_export($menu_list, true) . ';');
            }
        }

        return $this->success([
            'menuList' => $menu_list,
        ]);
    }

    protected function beforeSave($pk_val, &$data)
    {
        if (!empty($data['pwd'])) {
            $data['password'] = $this->passwordHash($data['pwd']);
        }
    }

    public function afterSave($pk_val, $data)
    {
        $role_ids = array_filter(array_unique($data['role_ids']));
        if ((int)$data['id'] == $pk_val) {
            UserRole::where('user_id', $pk_val)->delete();
            // 清除缓存
            $this->permission_service->getPermissionCacheKey(0, true);
        }
        $user_roles = [];
        foreach ($role_ids as $role_id) {
            $user_roles[] = [
                'user_id' => $pk_val,
                'role_id' => (int)$role_id,
            ];
        }
        if (!empty($user_roles)) {
            UserRole::insertOnDuplicateKey($user_roles, ['user_id', 'role_id']);
        }

        return true;
    }

    public function login()
    {
        $username = $this->request->input('username', '');
        $password = $this->request->input('password', '');
        if (!$username || !$password) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM);
        }
        $user = $this->getModel()->where('username', $username)->first();
        if (!$user || $user['status'] !== YES) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, '该用户不存在或已被禁用');
        }
        if ($user->password !== $this->passwordHash($password)) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM);
        }
        $data = [
            'iat' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addDay()->timestamp,
            'user_info' => [
                'id' => $user->id,
                'name' => $user->username,
                'alias_name' => $user->realname,
                'email' => '',
                'avatar' => $user->avatar,
                'mobile' => $user->mobile,
            ],
        ];
        $token = JWT::token($data);

        return $this->success([
            'id' => $user->id,
            'mobile' => $user->mobile,
            'name' => $user->realname,
            'avatar' => $user->avatar,
            'token' => $token,
        ]);
    }

    public function passwordHash($password)
    {
        return sha1(md5($password) . md5(config('password.salt')));
    }

    public function logout()
    {
        $user = $this->auth_service->logout();
        return $this->success();
    }

    public function act()
    {
        $attr = ['select' => ['id as value', 'realname as label']];
        $model = $this->getModel();
        $options = $model->search($attr, [], 'realname');

        return $this->success($options);
    }

    public function export()
    {
        $url = $this->request->input('url');
        $task = new ExportTasks();
        $task->name = $this->request->input('name');
        $task->list_api = $url;
        $task->filters = array_filter($this->request->input('filters'), function ($item) {
            return $item !== '';
        });
        $task->operator_id = $this->userId() ?? 0;
        if ((new ExportService())->getFirstSameTask($task->list_api, $task->filters, $task->operator_id)) { // 如果当天已经有相同过滤条件且还未成功生成文件的任务
            return $this->success([], '已经有相同的任务，请勿重复导出');
        }
        $task->current_page = 0;
        $saved = $task->save();
        log_operator($this->getModel(), '导出', $task->id ?? 0);
        $limit_max = ExportTasks::LIMIT_SIZE_MAX;
        return $saved ? $this->success([], '导出任务提交成功, 请在右上角小铃铛处查看任务状态，您将最多导出' . $limit_max . '条数据。') : $this->fail(ErrorCode::CODE_ERR_SERVER, '导出失败');
    }

    public function exportTasks()
    {
        /** @var ExportService $export */
        $export = make(ExportService::class);
        $list = $export->getTasks(null, $this->auth_service->get('id'), [
            'id',
            'name',
            'status',
            'total_pages',
            'current_page',
            'download_url',
            'created_at',
        ]);

        return $this->success([
            'list' => $list,
        ]);
    }

    public function exportLimit()
    {
        $limit_max = ExportTasks::LIMIT_SIZE_MAX;
        return $this->success([
            'max' => $limit_max
        ]);
    }

    /**
     * 导出任务重试
     * 重新丢入队列
     *
     * @param int $id 重试id
     *
     * @return Mixed
     */
    public function exportTasksRetry($id)
    {
        $export_tasks = ExportTasks::find($id);
        $updated = false;
        if ($export_tasks) {
            $updated = $export_tasks->update([
                'status' => 0,
                'current_page' => 0
            ]);
        }
        return $this->success([
            'retry' => $updated
        ]);
    }
}
