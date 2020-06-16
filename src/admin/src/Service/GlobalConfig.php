<?php
namespace HyperfAdmin\Admin\Service;

use HyperfAdmin\Admin\Model\GlobalConfig as GlobalModel;
use HyperfAdmin\BaseUtils\Redis\Redis;

class GlobalConfig
{
    public static function setConfig($name, $value, $ext = [], $raw = true)
    {
        $namespace = '';
        if(($pos = strpos($name, '.')) !== false) {
            $namespace = substr($name, 0, $pos);
        }
        if($raw) {
            $value = json_encode($value);
        }
        $ins = [
            'name' => $name,
            'value' => $value,
            'namespace' => $namespace,
        ];
        if($ext) {
            $ins = array_merge($ins, $ext);
        }
        $model = GlobalModel::query();
        $model->getConnection()->beginTransaction();
        $id = $model->where('name', $name)->value('id');
        if($id) {
            $res = $model->where('id', $id)->update($ins);
        } else {
            $res = $model->insert($ins);
        }
        if(empty($res)) {
            $model->getConnection()->rollBack();

            return false;
        }
        $model->getConnection()->commit();

        return true;
    }

    public static function getConfig($name, $default = null)
    {
        $cache_key = self::getCacheKey($name);
        $data = Redis::get($cache_key);
        if($data !== false) {
            return my_json_decode($data);
        }
        $data = GlobalModel::query()->where('name', $name)->select('value')->first()->toArray();
        $data = $data ?: $default;
        Redis::setex($cache_key, json_encode($data, JSON_UNESCAPED_UNICODE), 5 * MINUTE);

        return $data;
    }

    public static function getCacheKey($name)
    {
        return "omsapi:global_config:{$name}";
    }

    public static function getIdByName($name)
    {
        return GlobalModel::query()->where('name', $name)->value('id');
    }
}
