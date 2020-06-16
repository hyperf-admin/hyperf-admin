<?php

return [
    'databases' => [
        'hyperf_admin' => db_complete([
            'host' => env('HYPERF_ADMIN_DB_HOST'),
            'database' => env('HYPERF_ADMIN_DB_NAME', 'hyperf_admin'),
            'username' => env('HYPERF_ADMIN_DB_USER'),
            'password' => env('HYPERF_ADMIN_DB_PWD'),
        ]),
    ],
    'init_routes' => [
        __DIR__ . '/routes.php'
    ],
    'user_info_cache_prefix' => 'hyperf_admin_userinfo_',
    'admin_cookie_name' => 'hyperf_admin_token',
    // 脚手架预置权限
    'scaffold_permissions' => [
        'list' => [
            'label' => '列表',
            'type' => 2,
            'permission' => 'GET::/*/info,GET::/*/list,GET::/*/list.json,GET::/*/childs/{id:\d+},GET::/*/act,GET::/*/notice',
        ],
        'create' =>[
            'label' => '新建',
            'path' => '/form',
            'type' => 1,
            'permission' =>'GET::/*/form,GET::/*/form.json,POST::/*/form',
        ],
        'edit' =>[
            'label' => '编辑',
            'path' => '/:id',
            'type' => 1,
            'permission' => 'GET::/*/{id:\d+},GET::/*/{id:\d+}.json,POST::/*/{id:\d+},GET::/*/newversion/{id:\d+}/{last_ver_id:\d+}',
        ],
        'rowchange' =>[
            'label' => '行编辑',
            'type' => 2,
            'permission' => 'POST::/*/rowchange/{id:\d+}'
        ],
        'delete' => [
            'label' => '删除',
            'type' => 2,
            'permission' => 'POST::/*/delete',
        ],
        'import' => [
            'label' => '导入',
            'type' => 2,
            'permission' => 'POST::/*/import',
        ],
        'export' => [
            'label' => '导出',
            'type' => 2,
            'permission' => 'POST::/*/export',
        ],
    ],
];
