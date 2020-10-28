<?php
namespace HyperfAdmin\Admin\Controller;

use Hyperf\Utils\Str;
use HyperfAdmin\Admin\Model\FrontRoutes;
use HyperfAdmin\Admin\Service\CommonConfig;
use HyperfAdmin\Admin\Service\Menu;
use HyperfAdmin\Admin\Service\ModuleProxy;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Guzzle;

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
        
        if (isset($config['system_module']) && !$this->auth_service->isSupperAdmin()) {
            $user_id = $this->auth_service->get('id');

            $role_ids = $this->permission_service->getUserRoleIds($user_id);

            $router_ids = $this->permission_service->getRoleMenuIds($role_ids);

            foreach ($config['system_module'] as $module_key => $module_value) {
                $routers = make(Menu::class)->tree([
                    'module' => $module_value['name'],
                    'id' => $router_ids,
                ]);

                if(empty($routers)) unset($config['system_module'][$module_key]);
            }
        }

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

    public function listInfo(int $id)
    {
        $config = FrontRoutes::query()->find($id)->getAttributeValue("config");
        $this->options = $config;
        return $this->info();
    }

    public function listDetail(int $id)
    {
        $config = FrontRoutes::query()->find($id)->getAttributeValue("config");
        $listApi = $config['listApi'] ?? '';
        if (!$listApi) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '脚手架配置错误, 缺少列表接口');
        }
        try {
            return Guzzle::proxy($listApi, $this->request);
        } catch (\Exception $e) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '外部接口转发失败 ' . $e->getMessage());
        }
    }

    public function formInfo($route_id, $id)
    {
        $config = FrontRoutes::query()->find($route_id)->getAttributeValue("config");
        try {
            $this->options = $config;
            $form = $this->form();
            if ($id) {
                // todo token or aksk
                $getApi = str_var_replace($config['getApi'] ?? '', ['id' => $id]);
                $result = Guzzle::proxy($getApi, $this->request);
                if ($result['code'] !== 0) {
                    return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '外部接口转发失败 ' . $result['message'] ?? '');
                }
                foreach ($form['payload']['form'] as &$item) {
                    $item['value'] = $result['payload'][$item['field']] ?? null;
                    unset($item);
                }
            }
            return $form;
        } catch (\Exception $e) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '外部接口转发失败 ' . $e->getMessage());
        }
    }

    public function formSave($route_id, $id)
    {
        $config = FrontRoutes::query()->find($route_id)->getAttributeValue("config");
        $saveApi = str_var_replace($config['saveApi'] ?? '', ['id' => $id]);
        if (!$saveApi) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '脚手架配置错误, 缺少列表接口');
        }
        try {
            return Guzzle::post($saveApi, $this->request);
        } catch (\Exception $e) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '外部接口转发失败 ' . $e->getMessage());
        }
    }

    public function delete()
    {
    }

    public function proxy()
    {
        $proxyUrl = $this->request->query('proxy_url');

        if (!$proxyUrl) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '脚手架配置错误, 缺少列表接口');
        }
        try {
            return Guzzle::proxy($proxyUrl, $this->request);
        } catch (\Exception $e) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM, '外部接口转发失败 ' . $e->getMessage());
        }
    }
}
