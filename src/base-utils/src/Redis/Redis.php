<?php
namespace HyperfAdmin\BaseUtils\Redis;

use Hyperf\Redis\RedisFactory;
use HyperfAdmin\BaseUtils\Log;

/**
 * @mixin \Redis
 * @method static get($key)
 * @method static set($key, $val)
 * @method static setex($key, $ttl, $val)
 */
class Redis
{
    private $pool;

    /**
     * @var \Redis
     */
    private $redis;

    public function __construct($pool = 'default')
    {
        $this->pool = $pool;
        $this->redis = container(RedisFactory::class)->get($pool);
    }

    public static function conn($pool = 'default')
    {
        return new self($pool);
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->$name($arguments);
        }

        $start_time = microtime(true);
        $ret = $this->redis->$name(...$arguments);
        $end_time = microtime(true);
        $use_time = round(($end_time - $start_time) * 1000, 2);
        $level = $use_time > 100 ? 'error' : 'debug';
        Log::get('redis')->$level('redis call ' . $name, [
            'cluster' => $this->pool,
            'key' => $name,
            'use_time' => $use_time,
        ]);

        return $ret;
    }

    public static function __callStatic($name, $arguments)
    {
        return self::conn()->$name(...$arguments);
    }

    public function beyondFrequency($key, $duration, $limit)
    {
        $num = $this->redis->incr($key);
        if ($num == 1) {
            $this->redis->expire($key, $duration);
        }
        $ttl = $this->redis->ttl($key);
        if ($ttl == -1) {
            $this->redis->expire($key, $duration);
        }
        if ($num > $limit) {
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @param int    $expired
     * @param mixed  $callable
     *
     * @return array|null
     */
    public function getOrSet(string $name, int $expired, callable $callable)
    {
        if ($this->redis->exists($name)) {
            Log::get('redis')->info(sprintf('get %s from cache', $name));

            return json_decode($this->redis->get($name), true);
        }
        $data = call($callable);
        if ($data) {
            $this->redis->setex($name, $expired, json_encode($data));
        }

        return $data;
    }
}
