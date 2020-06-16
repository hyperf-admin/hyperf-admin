<?php
namespace HyperfAdmin\RuleEngine\Context;

abstract class ContextPluginAbstract implements ContextPluginInterface, \ArrayAccess
{
    protected $persistence = true;

    protected $data = [];

    public function keys(): array
    {
        try {
            $ref = new \ReflectionClass($this);
            $methods = $ref->getMethods();
            $keys = [];
            foreach($methods as $method) {
                if(strpos($method->name, 'get') === 0) {
                    $keys[] = $this->camel2snake(str_replace('get', '', $method->name));
                }
            }

            return $keys;
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    public function isPersistence()
    {
        return $this->persistence;
    }

    public function snake2camel($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    public function camel2snake($str)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $str, $matches);
        $ret = $matches[0];
        foreach($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    public function offsetGet($offset)
    {
        $offset = str_replace("\001", '.', $offset);
        preg_match('/^([a-zA-Z._0-9]+)\((.*)\)$/', $offset, $m);
        $param = '';
        if($m) {
            $offset = $m[1];
            $param = $m[2];
        }
        if($this->isPersistence() && isset($this->data[$offset])) {
            return $this->data[$offset];
        }
        $params = explode(',', $param);
        $method = 'get' . $this->snake2camel($offset);
        if(method_exists($this, $method)) {
            $val = $this->$method(...$params);
            if($this->isPersistence()) {
                $this->data[$offset] = $val;
            }

            return $val;
        }

        return null;
    }

    public function offsetExists($offset)
    {
        $offset = str_replace("\001", '.', $offset);
        preg_match('/^([a-zA-Z._0-9]+)\((.*)\)$/', $offset, $m);
        if($m) {
            $offset = $m[1];
        }
        $method = 'get' . $this->snake2camel($offset);

        return method_exists($this, $method);
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }
}
