<?php
namespace HyperfAdmin\Admin\Service;

use HyperfAdmin\Admin\Model\CommonConfig as CommonConfigModel;

class CommonConfig
{
    public static function getNamespaces()
    {
        return CommonConfigModel::query()
                   ->where('namespace', 'system')
                   ->where('name', 'namespace')
                   ->first()
                   ->toArray()['rules'] ?? [];
    }

    public static function getValue($namespace, $name, $default = [])
    {
        if($record = CommonConfigModel::query()
            ->where('namespace', $namespace)
            ->where('name', $name)
            ->first()) {
            return $record->toArray()['value'];
        }

        return $default;
    }

    public static function getConfigByName($name)
    {
        $conf = CommonConfigModel::query()->where(['name' => $name])->select([
                'id',
                'rules',
                'value',
            ])->first();
        if(!$conf) {
            return false;
        }

        return $conf->toArray();
    }

    public static function getValByName($name)
    {
        $value = CommonConfigModel::query()->where(['name' => $name])->value('value');

        return $value ?: [];
    }
}
