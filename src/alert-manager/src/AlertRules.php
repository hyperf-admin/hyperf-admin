<?php
namespace HyperfAdmin\AlertManager;

use HyperfAdmin\BaseUtils\Redis\Redis;

class AlertRules
{
    private $key = 'alert_manager:rules';

    public function get()
    {
        $rules = Redis::get($this->key);
        if($rules) {
            return json_decode($rules, true);
        }

        return [];
    }

    public function set(array $rules)
    {
        return Redis::set($this->key, json_encode($rules));
    }
}
