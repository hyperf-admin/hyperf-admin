<?php

use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Context;
use Hyperf\Utils\Str;

function removeComment($content)
{
    return preg_replace("/(\/\*.*\*\/)|(#.*?\n)|(\/\/.*?\n)/s", '', str_replace([
        "\r\n",
        "\r",
    ], "\n", $content));
}

//function df_db_conn($pool)
//{
//    return Db::connection('data_focus_' . $pool);
//}
function df_db_query($sql, $pool)
{
    if (!Str::startsWith($sql, ['select', 'SELECT'])) {
        throw new \Exception('只允许 SELECT 语句');
    }
    if (config('databases.' . $pool)) {
        $pool_name = $pool;
    }
    if (config('databases.data_focus_' . $pool)) {
        $pool_name = 'data_focus_' . $pool;
    }
    if (!isset($pool_name)) {
        throw new Exception(sprintf('dsn [%s] not found', $pool));
    }
    $ret = Db::connection($pool_name)->select($sql);
    df_set_context('last_sql', $sql);
    df_collect('sql_logs', $sql);
    if (is_array($ret)) {
        $ret = array_map(function ($item) {
            return (array)$item;
        }, $ret);
    }

    return $ret;
}

function df_db_last_sql()
{
    return df_get_context('last_sql');
}

function redis_conn($pool)
{
    return (new RedisFactory(container(ConfigInterface::class)))->get($pool);
}

function df_redis_get($pool, $key)
{
    if (config('redis.' . $pool)) {
        $pool_name = $pool;
    }
    if (config('redis.data_focus_' . $pool)) {
        $pool_name = 'data_focus_' . $pool;
    }
    if (!isset($pool_name)) {
        throw new Exception(sprintf('redis [%s] not found', $pool, 500));
    }

    return redis_conn($pool_name)->get($key);
}

function df_redis_set($pool, $key, $val, $ttl = 60)
{
    if (config('redis.' . $pool)) {
        $pool_name = $pool;
    }
    if (config('redis.data_focus_' . $pool)) {
        $pool_name = 'data_focus_' . $pool;
    }
    if (!isset($pool_name)) {
        throw new Exception(sprintf('redis [%s] not found', $pool, 500));
    }

    return redis_conn($pool_name)->setex($key, $ttl, $val);
}

function df_json_decode($json, $default = [])
{
    if (!$json) {
        return $default;
    }
    $json = preg_replace('@//[^"]+?$@mui', '', $json);
    $json = preg_replace('@^\s*//.*?$@mui', '', $json);
    $json = $json ? @json_decode($json, true) : $default;
    if (is_null($json)) {
        $json = $default;
    }

    return $json;
}

function df_set_context($key, $val)
{
    return Context::set('data_focus_' . $key, $val);
}

function df_get_context($key, $default = null)
{
    return Context::get('data_focus_' . $key, $default);
}

function df_collect($key, $val)
{
    $current = df_collected($key);
    $current[] = $val;

    return Context::set('data_focus_' . $key, json_encode($current, JSON_UNESCAPED_UNICODE));
}

function df_collected($key)
{
    return df_json_decode(Context::get('data_focus_' . $key));
}

function df_collected_clear($key)
{
    Context::set('data_focus_' . $key, '[]');
}

function df_dump(...$arg)
{
    foreach ($arg as $index => $value) {
        $dump = highlight_string("<?php\n" . var_export($value, true) . "; \n?>", true);
        $dump = Str::replaceArray('<span style="color: #0000BB">&lt;?php<br /></span>', [''], $dump);
        $dump = Str::replaceArray('<span style="color: #0000BB">?&gt;</span>', [''], $dump);
        df_collect('df_debug_dump', $dump);
    }
}

function df_dump_get()
{
    return df_collected('df_debug_dump');
}

function df_dump_clear()
{
    df_collected_clear('df_debug_dump');
}
