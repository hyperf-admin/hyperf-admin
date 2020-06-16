<?php
namespace HyperfAdmin\BaseUtils\Redis;

use Hyperf\Redis\RedisFactory;
use HyperfAdmin\BaseUtils\Log;

class Redis
{
    /**
     * @param string $name redis pool name
     *
     * @return \Redis
     */
    public static function connection($name = 'default')
    {
        return container(RedisFactory::class)->get($name);
    }

    public static function set($name, $value, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $re = $redis->set($name, $value);
        $end_time = microtime(true);
        Log::get('redis')->debug('set', [
            'cluster' => $cluster,
            'key' => $name,
            'use_time' => $end_time - $start_time,
            'result' => $re,
        ]);

        return $re;
    }

    public static function setex($name, $value, $expire, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $re = $redis->setex($name, $expire * 1, $value);
        $end_time = microtime(true);
        Log::get('redis')->debug('setex', [
            'cluster' => $cluster,
            'key' => $name,
            'ttl' => $expire,
            'use_time' => $end_time - $start_time,
            'result' => $re,
        ]);

        return $re;
    }

    public static function get($name, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $re = $redis->get($name);
        $end_time = microtime(true);
        Log::get('redis')->debug('get', [
            'cluster' => $cluster,
            'key' => $name,
            'use_time' => $end_time - $start_time,
            'result' => $re,
        ]);

        return $re;
    }

    public static function exists($name, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $re = $redis->exists($name);
        $end_time = microtime(true);
        Log::get('redis')->debug('exist', [
            'cluster' => $cluster,
            'key' => $name,
            'use_time' => $end_time - $start_time,
            'result' => $re,
        ]);

        return $re;
    }

    public static function incr($key, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $ret = $redis->incr($key);
        $end_time = microtime(true);
        Log::get('redis')->debug('incr', [
            'cluster' => $cluster,
            'key' => $key,
            'use_time' => $end_time - $start_time,
            'result' => $ret,
        ]);

        return $ret;
    }

    public static function incrBy($key, $value, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $ret = $redis->incrBy($key, $value);
        $end_time = microtime(true);
        Log::get('redis')->debug('incrBy', [
            'cluster' => $cluster,
            'key' => $key,
            'use_time' => $end_time - $start_time,
            'result' => $ret,
        ]);

        return $ret;
    }

    public static function expire($key, $ttl, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $ret = $redis->expire($key, $ttl);
        $end_time = microtime(true);
        Log::get('redis')->debug('expire', [
            'cluster' => $cluster,
            'key' => $key,
            'use_time' => $end_time - $start_time,
            'result' => $ret,
        ]);

        return $ret;
    }

    public static function ttl($key, $cluster = 'default')
    {
        $redis = self::connection($cluster);
        $start_time = microtime(true);
        $ret = $redis->ttl($key);
        $end_time = microtime(true);
        Log::get('redis')->debug('ttl', [
            'cluster' => $cluster,
            'key' => $key,
            'use_time' => $end_time - $start_time,
            'result' => $ret,
        ]);

        return $ret;
    }

    public static function beyondFrequency($key, $duration, $limit, $cluster = 'default')
    {
        $num = self::incr($key, $cluster);
        if($num == 1) {
            self::expire($key, $duration, $cluster);
        }
        $ttl = self::ttl($key, $cluster);
        if($ttl == -1) {
            self::expire($key, $duration, $cluster);
        }
        if($num > $limit) {
            return true;
        }

        return false;
    }

    public static function hGet($key, $hashKey, $cluster = 'default')
    {
        $redis = self::connection($cluster);

        return $redis->hGet($key, $hashKey);
    }

    public static function hSet($key, $hashKey, $value, $cluster = 'default')
    {
        $redis = self::connection($cluster);

        return $redis->hSet($key, $hashKey, $value);
    }

    public static function hGetAll($key, $cluster = 'default')
    {
        $redis = self::connection($cluster);

        return $redis->hGetAll($key);
    }

    public static function hMset($key, $value, $cluster = 'default')
    {
        $redis = self::connection($cluster);

        return $redis->hMset($key, $value);
    }

    /**
     * @param string $name
     * @param int    $expired
     * @param mixed  $callable
     * @param string $cluster
     *
     * @return array|null
     */
    public static function getOrSet(string $name, int $expired, callable $callable, $cluster = 'default')
    {
        if(self::exists($name)) {
            Log::get('redis')->info(sprintf('get %s from cache', $name));

            return json_decode(self::get($name, $cluster), true);
        }
        $data = call($callable);
        if($data) {
            self::setex($name, json_encode($data), $expired, $cluster);
        }

        return $data;
    }
}
