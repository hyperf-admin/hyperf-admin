<?php
namespace HyperfAdmin\ConfigCenter\Service;

use HyperfAdmin\BaseUtils\Redis\Redis;
use HyperfAdmin\ConfigCenter\Model\ConfigCenter;
use Hyperf\Utils\Arr;

class ConfigCenterService
{
    protected $version_key = 'hyperf_admin:config_center:version';

    protected $config_key = 'hyperf_admin:config_center:cache';

    public function mergeAll()
    {
        $all = ConfigCenter::query()->select(['path', 'value'])->get()->toArray();
        $map = array_to_kv($all, 'path', 'value');
        $map = array_sort_by_key_length($map, SORT_ASC);
        $config = [];
        foreach ($map as $key => $val) {
            Arr::set($config, $key, $val);
        }
        return $config;
    }

    public function save()
    {
        $config = $this->mergeAll();
        $config = json_encode($config);
        Redis::set($this->config_key, $config);
        Redis::set($this->version_key, date('YmdHis') . md5($config));
    }

    public function version()
    {
        return Redis::get($this->version_key);
    }

    public function get()
    {
        $config = Redis::get($this->config_key);

        return $config ? json_decode($config, true) : [];
    }
}
