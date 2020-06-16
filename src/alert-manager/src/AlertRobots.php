<?php
namespace HyperfAdmin\AlertManager;

use HyperfAdmin\BaseUtils\Redis\Redis;

class AlertRobots
{
    private $key = 'alert_manager:robots';

    public function get()
    {
        $rules = Redis::get($this->key);
        if($rules) {
            return json_decode($rules, true);
        }

        return [];
    }

    public function set(array $value)
    {
        return Redis::set($this->key, json_encode($value));
    }
}
