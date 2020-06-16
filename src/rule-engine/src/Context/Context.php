<?php
namespace HyperfAdmin\RuleEngine\Context;

use HyperfAdmin\Util\Filter\ArrayHelper;

class Context implements \ArrayAccess
{
    public $plugins = [];

    protected $key_map = [];

    protected $custom_data = [];

    public function register($plugin)
    {
        if(!($plugin instanceof ContextPluginInterface)) {
            throw new \Exception('invalid plugin');
        }
        $name = $plugin->name();
        if(isset($this->plugins[$name])) {
            return false;
        }
        $this->plugins[$name] = $plugin;
        $keys = $plugin->keys();
        foreach($keys as $item) {
            $this->key_map[$name . '.' . $item] = $name;
        }

        return $this;
    }

    public function offsetGet($offset)
    {
        if(isset($this->custom_data[$offset])) {
            return $this->custom_data[$offset];
        }
        preg_match('/^([a-zA-Z._0-9]+)\((.*)\)$/', $offset, $m);
        $param = '';
        if($m) {
            $offset = $m[1];
            $param = $m[2];
            $params = explode(',', $param);
            $context = $this;
            $params = array_map(function ($item) use ($context) {
                $real = ArrayHelper::array_get($item, $context);
                if($real === null) {
                    return $item;
                }

                return $real;
            }, $params);
            $param = implode(',', $params);
        }
        $param = str_replace('.', "\001", $param);
        if(isset($this->key_map[$offset])) {
            [$plugin_name, $key] = explode('.', $offset, 2);

            return ArrayHelper::array_get($param ? "{$key}({$param})" : $key, $this->plugins[$plugin_name]);
        }
        foreach($this->plugins as $plugin) {
            $key = explode('.', $offset, 2)[0];
            if(in_array($key, $plugin->keys())) {
                return ArrayHelper::array_get($param ? "{$offset}($param)" : $offset, $plugin);
            }
        }

        return false;
    }

    public function offsetExists($offset)
    {
        if(isset($this->custom_data[$offset])) {
            return true;
        }
        preg_match('/^([a-zA-Z._0-9]+)\((.*)\)$/', $offset, $m);
        if($m) {
            $offset = $m[1];
        }
        if(isset($this->key_map[$offset])) {
            return true;
        }
        foreach($this->plugins as $plugin) {
            $key = explode('.', $offset, 2)[0];
            if(in_array($key, $plugin->keys())) {
                return true;
            }
        }

        return false;
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

    public function setCustomContext($data)
    {
        $this->custom_data = $data;

        return $this;
    }

    public function clearCustomContext()
    {
        return $this->setCustomContext([]);
    }
}
