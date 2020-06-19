<?php
namespace HyperfAdmin\Admin\Controller;

use Hyperf\Utils\Str;
use HyperfAdmin\Admin\Service\CommonConfig;
use HyperfAdmin\Admin\Service\ModuleProxy;

class SystemController extends AdminAbstractController
{
    public function state()
    {
        $swoole_server = swoole_server();

        return $this->success([
            'state' => $swoole_server->stats(),
        ]);
    }

    public function config()
    {
        $config = CommonConfig::getValue('system', 'website_config', [
            'open_export' => false,
            'navbar_notice' => '',
        ]);

        return $this->success($config);
    }

    public function routes()
    {
        $module_proxy = make(ModuleProxy::class);
        if ($module_proxy->needProxy()) {
            return $this->success($module_proxy->request()['payload']);
        }

        $kw = $this->request->input('kw', '');
        $routes = $this->permission_service->getSystemRouteOptions();
        $routes = array_filter($routes, function ($item) use ($kw) {
            return Str::contains($item['value'], $kw);
        });
        return $this->success(array_values($routes));
    }
}
