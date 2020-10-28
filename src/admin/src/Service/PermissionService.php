<?php
namespace HyperfAdmin\Admin\Service;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Utils\Str;
use HyperfAdmin\Admin\Model\CommonConfig;
use HyperfAdmin\Admin\Model\FrontRoutes;
use HyperfAdmin\Admin\Model\Role;
use HyperfAdmin\Admin\Model\RoleMenu;
use HyperfAdmin\Admin\Model\UserRole;
use HyperfAdmin\BaseUtils\Redis\Redis;
use HyperfAdmin\Admin\Service\CommonConfig as CommonConfigService;

class PermissionService
{
    public $scaffold_actions = [
        'newVersion', 'rowChange', 'getTreeNodeChilds', 'export', 'import', 'save', 'delete', 'info'
    ];

    /**
     * 解析系统路由
     *
     * @return array
     */
    public function getSystemRouteOptions()
    {
        $router = container(DispatcherFactory::class)->getRouter('http');
        $data = $router->getData();
        $options = [];
        foreach($data as $routes_data) {
            foreach($routes_data as $http_method => $routes) {
                $route_list = [];
                if(isset($routes[0]['routeMap'])) {
                    foreach($routes as $map) {
                        array_push($route_list, ...$map['routeMap']);
                    }
                } else {
                    $route_list = $routes;
                }
                foreach($route_list as $route => $v) {
                    // 过滤掉脚手架页面配置方法
                    $callback = is_array($v) ? ($v[0]->callback) : $v->callback;
                    if(!is_array($callback)) {
                        continue;
                    }
                    $route = is_string($route) ? rtrim($route) : rtrim($v[0]->route);
                    $route_key = "$http_method::{$route}";
                    $options[] = [
                        'value' => $route_key,
                        'label' => $route_key,
                    ];
                }
            }
        }

        return $options;
    }

    public function getRolePermissionValues($router_ids, $module = 'system')
    {
        if(empty($router_ids)) {
            return [];
        }
        $data = [];
        $routers = make(Menu::class)->tree([
            'module' => $module,
            'id' => $router_ids,
        ]);
        if(!empty($routers)) {
            $paths = array_keys(tree_2_paths($routers, $module));
            foreach($paths as $path) {
                $data[] = explode('-', $path);
            }
        }

        return $data;
    }

    /**
     * 构造角色权限设置options
     *
     * @return array
     */
    public function getPermissionOptions($role_id = 0)
    {
        // todo 配置化
        $modules = make(CommonConfigService::class)->getValue('system', 'website_config')['system_module'];

        $options = [];
        $values = [];

        $router_ids = $this->getRoleMenuIds([$role_id]);

        foreach ($modules as $item) {
            $options[] = [
                'value' => $item['name'],
                'label' => $item['label'],
                'children' => make(Menu::class)->tree(['module' => $item['name']]),
            ];

            $values = array_merge($values, $this->getRolePermissionValues($router_ids, $item['name']));
        }

        return [$values, $options];
    }

    public function getAllRoleList($where = [], $fields = ['*'])
    {
        $model = new Role();
        $roles = $model->where2query($where)
            ->select($fields)
            ->orderByRaw('pid asc, sort desc')
            ->get();

        return $roles->toArray();
    }

    public function getRoleMenuIds($role_ids)
    {
        if(empty($role_ids)) {
            return [];
        }
        $routes = RoleMenu::query()
            ->distinct(true)
            ->select(['router_id'])
            ->whereIn('role_id', $role_ids)
            ->get()
            ->toArray();

        return $routes ? array_column($routes, 'router_id') : [];
    }

    public function getRoleTree()
    {
        $roles = make(Role::class)->search([
            'select' => ['id as value', 'name as label', 'pid'],
            'limit' => 200,
            'order_by' => 'sort desc',
        ], [
            'status' => YES,
        ], 'name', 'id', 'and', true);

        return generate_tree($roles, 'pid', 'value');
    }

    public function getUserRoleIds($user_id)
    {
        if(!$user_id) {
            return [];
        }

        return UserRole::query()
            ->select(['role_id'])
            ->where('user_id', $user_id)
            ->get()
            ->pluck('role_id')
            ->toArray();
    }

    public function getRoleUserIds($role_id)
    {
        if(!$role_id) {
            return [];
        }

        return UserRole::query()
            ->select(['user_id'])
            ->where('role_id', $role_id)
            ->get()
            ->pluck('user_id')
            ->toArray();
    }

    public function getMenuRoleIds($menu_id)
    {
        if(!$menu_id) {
            return [];
        }

        return RoleMenu::query()
            ->select(['role_id'])
            ->where('router_id', $menu_id)
            ->get()
            ->pluck('role_id')
            ->toArray();
    }

    public function getUserResource($user_id)
    {
        if(!$user_id) {
            return [];
        }
        $user_role_ids = $this->getUserRoleIds($user_id);
        $role_menu_ids = $this->getRoleMenuIds($user_role_ids);
        $list = make(FrontRoutes::class)->where2query([
            'id' => $role_menu_ids,
            'type' => ['>' => 0],
            'status' => YES,
        ])->distinct(true)->select([
            'http_method',
            'path',
            'is_scaffold',
            'permission',
            'scaffold_action',
        ])->get()->toArray();
        $resources = [];
        foreach($list as $route) {
            if(Str::contains($route['permission'], '::')) {
                $permissions = array_filter(explode(',', $route['permission']));
                foreach($permissions as $permission) {
                    [
                        $http_method,
                        $uri,
                    ] = array_filter(explode('::', $permission, 2));
                    $resources[] = [
                        'http_method' => $http_method,
                        'uri' => $uri,
                    ];
                }
            } else {
                // 这段代码为兼容老的数据
                $paths = array_filter(explode('/', $route['path']));
                $suffix = array_pop($paths);
                $prefix = implode('/', $paths);
                if($suffix == 'list') {
                    $action_conf = config("scaffold_permissions.list.permission");
                    $scaffold_permissions = array_filter(explode(',', str_replace('/*/', "/{$prefix}/", $action_conf)));
                    foreach($scaffold_permissions as $scaffold_permission) {
                        [
                            $http_method,
                            $uri,
                        ] = array_filter(explode('::', $scaffold_permission, 2));
                        $resources[] = [
                            'http_method' => $http_method,
                            'uri' => $uri,
                        ];
                    }
                }
                if(empty($route['permission'])) {
                    continue;
                }
                $resources[] = [
                    'http_method' => FrontRoutes::$http_methods[$route['http_method']],
                    'uri' => $route['permission'],
                ];
            }
        }
        $user_open_apis = $this->getOpenResourceList('user_open_api');
        $system_user_open = config('system.user_open_resource', ['/system/routes']);
        return array_merge($resources, $user_open_apis, $system_user_open);
    }

    public function getOpenResourceList($field = 'open_api')
    {
        $open_apis = CommonConfig::query()->where([
                'namespace' => 'system',
                'name' => 'permissions',
            ])->value('value')[$field] ?? [];
        $data = [];
        foreach($open_apis as $route) {
            [$http_method, $uri] = explode("::", $route, 2);
            $data[] = compact('http_method', 'uri');
        }

        return $data;
    }

    public function getResourceDispatcher($user_id = 0, $auth_type = FrontRoutes::RESOURCE_OPEN)
    {
        $cache_key = $this->getPermissionCacheKey($user_id);
        $options = [
            'routeParser' => 'FastRoute\\RouteParser\\Std',
            'dataGenerator' => 'FastRoute\\DataGenerator\\GroupCountBased',
            'dispatcher' => 'FastRoute\\Dispatcher\\GroupCountBased',
            'routeCollector' => 'FastRoute\\RouteCollector',
        ];
        if(!$dispatch_data = json_decode(Redis::get($cache_key), true)) {
            /** @var RouteCollector $routeCollector */
            $route_collector = new $options['routeCollector'](new $options['routeParser'], new $options['dataGenerator']);
            $this->processUserResource($route_collector, $user_id, $auth_type);
            $dispatch_data = $route_collector->getData();
            if(!empty($dispatch_data)) {
                Redis::setex($cache_key, DAY, json_encode($dispatch_data));
            }
        }

        return new $options['dispatcher']($dispatch_data);
    }

    protected function processUserResource(RouteCollector $r, $user_id, $auth_type)
    {
        $resources = $auth_type == FrontRoutes::RESOURCE_OPEN ? $this->getOpenResourceList() : $this->getUserResource($user_id);
        $route_keys = [];
        foreach($resources as $resource) {
            if(!isset($resource['uri']) || !$resource['uri']) {
                continue;
            }
            $route_key = "{$resource['http_method']}::{$resource['uri']}";
            if(in_array($route_key, $route_keys)) {
                continue;
            }
            $route_keys[] = $route_key;
            $r->addRoute($resource['http_method'], $resource['uri'], '');
        }
    }

    public function hasPermission($uri, $method = 'GET')
    {
        $auth_service = make(AuthService::class);
        // 用户为超级管理员
        if($auth_service->isSupperAdmin()) {
            return true;
        }
        $user = $auth_service->user();
        $dispatcher = $this->getResourceDispatcher($user['id'] ?? 0, FrontRoutes::RESOURCE_NEED_AUTH);
        $route_info = $dispatcher->dispatch($method, $uri);

        return $route_info[0] === $dispatcher::FOUND;
    }

    public function isOpen($uri, $method)
    {
        $routes = container(DispatcherFactory::class)
            ->getDispatcher('http')
            ->dispatch($method, $uri);
        if($routes[0] !== Dispatcher::FOUND) {
            return false;
        }
        if ($routes[1] instanceof Handler) {
            if ($routes[1]->callback instanceof \Closure) {
                return true;
            }
            [$controller, $action] = $this->prepareHandler($routes[1]->callback);
        } else {
            return false;
        }
        $controllerInstance = container($controller);
        if (isset($controllerInstance->open_resources) && in_array($action, $controllerInstance->open_resources)) {
            return true;
        }

        // 获取开放的资源
        $dispatcher = $this->getResourceDispatcher();
        $route_info = $dispatcher->dispatch($method, $uri);

        return $route_info[0] === $dispatcher::FOUND;
    }

    public function can($uri, $method)
    {
        if($this->isOpen($uri, $method)) {
            return true;
        }
        if($this->hasPermission($uri, $method)) {
            return true;
        }

        return false;
    }

    public function getPermissionCacheKey($user_id = 0, $force = false)
    {
        $cache_key = 'hyperf_admin_permission_cache:key_map';
        $new_key = "hyperf_admin_permission_cache:" . md5(time() . Str::random(6));
        $cache_value = !$force ? json_decode(Redis::get($cache_key), true) : [];
        if(!isset($cache_value[$user_id])) {
            $cache_value[$user_id] = $new_key;
            Redis::set($cache_key, json_encode($cache_value));
        }

        return $cache_value[$user_id];
    }

    protected function prepareHandler($handler): array
    {
        if(is_string($handler)) {
            if(strpos($handler, '@') !== false) {
                return explode('@', $handler);
            }

            return explode('::', $handler);
        }
        if(is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new \RuntimeException('Handler not exist.');
    }
    
    public function getModules($router_ids)
    {
        if(!$router_ids) {
            return [];
        }

        return FrontRoutes::query()
            ->select(['module'])
            ->whereIn('id', $router_ids)
            ->get()
            ->pluck('module')
            ->toArray();
    }
}
