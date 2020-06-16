<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Redis\Redis;
use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int    $id
 * @property string $namespace
 * @property string $name
 * @property string $title
 * @property string $remark
 * @property string $rules
 * @property string $value
 */
class GlobalConfig extends BaseModel
{
    protected $table = 'global_config';

    protected $connection = 'hyperf_admin';

    protected $fillable = [
        'namespace',
        'name',
        'title',
        'remark',
        'rules',
        'value',
    ];

    protected $casts = ['id' => 'int'];

    const CACHE_PREFIX = 'omsapi_config_';

    const CACHE_TIME = 60 * 5;

    const VALUE_STATUS_YES = 1; //启用

    const VALUE_STATUS_NO = 0; //禁用

    const VALUE_STATUS_MAP = [
        self::VALUE_STATUS_YES => '启用',
        self::VALUE_STATUS_NO => '禁用',
    ];

    const NAMESPACE_AB_WHITE_LIST = 'ab_white_list';

    public static function getConfig($name, $default = null, $cache = false)
    {
        $cache_key = self::getConfigCache($name);
        $cache_config = Redis::get($cache_key);
        if($cache_config) {
            return json_decode($cache_config, true);
        }
        $item = static::query()->where('name', $name)->get()->toArray();
        if(empty($item)) {
            return $default;
        }
        $config = json_decode($item[0]['value'], true);
        if(is_null($config)) {
            return $default;
        }
        if($cache) {
            Redis::setex($cache_key, self::CACHE_TIME, json_encode($config));
        }

        return $config;
    }

    public static function getConfigCache($name)
    {
        return self::CACHE_PREFIX . $name;
    }

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

        return static::query()->updateOrInsert(['name' => $name], $ins);
    }
}
