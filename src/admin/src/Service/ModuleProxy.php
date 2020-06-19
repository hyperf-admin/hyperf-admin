<?php

namespace HyperfAdmin\Admin\Service;

use HyperfAdmin\BaseUtils\Guzzle;
use HyperfAdmin\BaseUtils\Redis\Redis;

class ModuleProxy
{
    protected $request;
    protected $modules;
    protected $target_module;
    public function __construct()
    {
        $this->request = request();
        $this->modules =  Redis::getOrSet('hyperf_admin:system_modules', 500, function () {
            $list = CommonConfig::getConfigByName('website_config')['value']['system_module'];
            array_change_v2k($list, 'name');
            return $list;
        });
        $this->target_module = $this->request->input('module') ?? (request_header('x-module')[0] ?? '');
    }

    public function needProxy()
    {
        $no_proxy = request_header('x-no-proxy')[0] ?? false;

        if ($no_proxy) {
            return false;
        }

        if (!isset($this->modules[$this->target_module])) {
            return false;
        }

        if ($this->modules[$this->target_module]['type'] != 'remote') {
            return false;
        }

        return true;
    }

    public function request()
    {
        return Guzzle::proxy($this->modules[$this->target_module]['remote_base_uri'] . $this->request->getUri()->getPath(), $this->request);
    }

    public function getTargetModule()
    {
        return $this->target_module;
    }
}
