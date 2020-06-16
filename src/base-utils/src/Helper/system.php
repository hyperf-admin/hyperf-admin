<?php

use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Router;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\Str;
use OSS\Core\OssException;
use HyperfAdmin\BaseUtils\AliyunOSS;
use HyperfAdmin\BaseUtils\Guzzle;
use HyperfAdmin\BaseUtils\Log;

if (file_exists(BASE_PATH . '/.env')) {
    Dotenv\Dotenv::create([BASE_PATH])->load();
}

if(!function_exists('server')) {
    function server()
    {
        return container(ServerFactory::class);
    }
}

if(!function_exists('swoole_server')) {
    /**
     * @return \Swoole\Server
     */
    function swoole_server()
    {
        return server()->getServer()->getServer();
    }
}

if(!function_exists('dispatcher')) {
    /**
     * @param $server_name
     *
     * @return \FastRoute\Dispatcher
     */
    function dispatcher(string $server_name = 'http')
    {
        return container(DispatcherFactory::class)->getDispatcher($server_name);
    }
}

if(!function_exists('register_route')) {
    function register_route($prefix, $controller, $callable = null)
    {
        Router::addGroup($prefix, function () use ($controller, $callable) {
            Router::get('/list.json', [$controller, 'info']);
            Router::get('/form.json', [$controller, 'form']);
            Router::get('/{id:\d+}.json', [$controller, 'edit']);
            Router::get('/info', [$controller, 'info']);
            Router::get('/form', [$controller, 'form']);
            Router::get('/{id:\d+}', [$controller, 'edit']);
            Router::get('/list', [$controller, 'list']);
            Router::post('/form', [$controller, 'save']);
            Router::post('/delete', [$controller, 'delete']);
            Router::post('/batchdel', [$controller, 'batchDelete']);
            Router::post('/{id:\d+}', [$controller, 'save']);
            Router::post('/rowchange/{id:\d+}', [$controller, 'rowChange']);
            Router::get('/childs/{id:\d+}', [$controller, 'getTreeNodeChilds']);
            Router::get('/newversion/{id:\d+}/{last_ver_id:\d+}', [
                $controller,
                'newVersion',
            ]);
            Router::post('/export', [$controller, 'export']);
            Router::get('/act', [$controller, 'act']);
            Router::post('/import', [$controller, 'import']);
            is_callable($callable) && $callable($controller);
        });
    }
}

if(!function_exists('move_local_file_to_oss')) {
    function move_local_file_to_oss($local_file_path, $oss_file_path, $private = false, $bucket = 'aliyuncs')
    {
        /** @var AliyunOSS $oss */
        $oss = make(AliyunOSS::class, ['bucket' => $bucket]);
        try {
            $method = $private ? 'uploadPrivateFile' : 'uploadFile';
            $oss->$method($oss_file_path, $local_file_path);
            $file_path = config('storager.aliyuncs.cdn') . '/' . $oss_file_path;
            if($private) {
                $file_path = oss_private_url($oss_file_path, MINUTE * 5, $bucket);
            }

            return [
                'file_path' => $file_path,
                'path' => 'oss/' . $oss_file_path,
            ];
        } catch (OssException $exception) {
            Log::get('move_local_file_to_oss')->error($exception->getMessage());

            return false;
        }
    }
}

if(!function_exists('oss_private_url')) {
    function oss_private_url($oss_file_path, $timeout = 60, $bucket = 'aliyuncs')
    {
        /** @var AliyunOSS $oss */
        $oss = make(AliyunOSS::class, ['bucket' => $bucket]);
        $key = preg_replace('@^oss/@', '', $oss_file_path);
        try {
            return str_replace('-internal', '', $oss->getSignUrl($key, $timeout));
        } catch (OssException $exception) {
            Log::get('oss_private_url')->error($exception->getMessage());

            return false;
        }
    }
}

if(!function_exists('call_self_api')) {
    function call_self_api($api, $params = [], $method = 'GET')
    {
        $headers = [
            'X-Real-IP' => '127.0.0.1',
        ];
        $info = Guzzle::request($method, "http://127.0.0.1:" . config('server.servers.0.port') . $api, $params, $headers);

        return $info['payload'] ?? [];
    }
}

if(!function_exists('select_options')) {
    function select_options($api, array $kws)
    {
        $ret = [];
        $chunk = array_chunk($kws, 100);
        foreach($chunk as $part) {
            $ret = array_merge($ret, call_self_api($api, ['kw' => implode(',', $part)]));
        }

        return $ret;
    }
}

if(!function_exists('process_list_filter')) {
    function process_list_filter($processes, $rule)
    {
        if(!$rule) {
            return $processes;
        }
        if($ignore = $rule['ignore'] ?? false) {
            if(is_string($ignore) && $ignore === 'all') {
                $processes = [];
            }
            if(is_array($ignore)) {
                $processes = array_filter($processes, function ($item) use ($ignore) {
                    return !Str::startsWith($item, array_map(function ($each) {
                        return Str::replaceLast('*', '', $each);
                    }, $ignore));
                });
            }
        }
        if($active = $rule['active'] ?? []) {
            $processes = array_merge($processes, $active);
        }

        return $processes;
    }
}

if(!function_exists('get_sub_dir')) {
    function get_sub_dir($dir, $exclude = [])
    {
        $paths = [];
        $dirs = \Symfony\Component\Finder\Finder::create()
            ->in($dir)
            ->depth('<1')
            ->exclude((array)$exclude)
            ->directories();
        /** @var SplFileInfo $dir */
        foreach($dirs as $dir) {
            $paths[] = $dir->getRealPath();
        }

        return $paths;
    }
}

if(!function_exists('db_complete')) {
    function db_complete(array $conf)
    {
        return array_overlay($conf, [
            'port' => 3306,
            'driver' => 'mysql',
            'options' => [
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ],
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 20,
                'connect_timeout' => 10.0,
                'wait_timeout' => 100,
                'heartbeat' => -1,
                'max_idle_time' => (float)env('DB_MAX_IDLE_TIME', 60),
            ],
        ]);
    }
}

if (!function_exists('format_exception')) {
    function format_exception($throwable)
    {
        return make(FormatterInterface::class)->format($throwable);
    }
}
