<?php
namespace HyperfAdmin\BaseUtils;

use Hyperf\HttpServer\Router\DispatcherFactory;

class RoutesDispatcher extends DispatcherFactory
{
    public function initConfigRoute()
    {
        $routers_dir = BASE_PATH . '/config/routes';

        $router_files = array_merge(array_filter(array_map(function ($file) {
            if (file_exists($file)) {
                return $file;
            }
            return false;
        }, config('init_routes', []))));

        if (is_dir($routers_dir)) {
            $router_files = array_merge($router_files, get_dir_filename($routers_dir, 'php', true));
        }
        $this->routes = array_unique(array_filter(array_merge($this->routes, $router_files)));
        parent::initConfigRoute();
    }
}
