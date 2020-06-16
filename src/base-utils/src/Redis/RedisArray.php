<?php
namespace HyperfAdmin\BaseUtils\Redis;

use Hyperf\Redis\RedisFactory;

class RedisArray implements \ArrayAccess
{
    /** @var \Co\Redis */
    private $redis;

    private $name;

    public function __construct($name, $cluster = 'default')
    {
        $this->name = 'redis_array_access_' . $name;
        $this->redis = container(RedisFactory::class)->get($cluster);
    }

    public function offsetExists($offset)
    {
        return $this->redis->hExists($this->name, (string)$offset);
    }

    public function offsetGet($offset)
    {
        $val = $this->redis->hGet($this->name, (string)$offset);
        if(is_json_str($val)) {
            return json_decode($val, true);
        }

        return $val;
    }

    public function offsetSet($offset, $value)
    {
        return $this->redis->hSet($this->name, (string)$offset, is_array($value) ? json_encode($value) : $value);
    }

    public function offsetUnset($offset)
    {
        return $this->redis->hDel($this->name, (string)$offset);
    }

    public function __destruct()
    {
        //$this->redis->del($this->name);
    }

    public function count()
    {
        return $this->redis->hLen($this->name);
    }
}
