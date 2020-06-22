<?php

use GuzzleHttp\Client;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;

if (!function_exists('generate_tree')) {
    function generate_tree(array $array, $pid_key = 'pid', $id_key = 'id', $children_key = 'children', $callback = null)
    {
        if (!$array) {
            return [];
        }
        //第一步 构造数据
        $items = [];
        foreach ($array as $value) {
            if ($callback && is_callable($callback)) {
                $callback($value);
            }
            $items[$value[$id_key]] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = [];
        foreach ($items as $key => $value) {
            //如果pid这个节点存在
            if (isset($items[$value[$pid_key]])) {
                $items[$value[$pid_key]][$children_key][] = &$items[$key];
            } else {
                $tree[] = &$items[$key];
            }
        }

        return $tree;
    }
}

if (!function_exists('generate_checkbox_tree')) {
    function generate_checkbox_tree(array $array, array $checked_arr = [], $pid_key = 'pid', $id_key = 'id', $label_key = 'label')
    {
        $parents = [];
        //第一步 构造数据
        $items = [];
        foreach ($array as $value) {
            $items[$value[$id_key]] = [
                'label' => $value[$label_key],
                'value' => $value[$id_key],
            ];
            if ($value[$pid_key] > 0) {
                $parents[$value[$id_key]] = $value[$pid_key];
            } else {
                $items[$value[$id_key]]['checkAll'] = false;
                $items[$value[$id_key]]['isIndeterminate'] = false;
                $items[$value[$id_key]]['checkList'] = in_array($value[$id_key], $checked_arr) ? [$value[$id_key]] : [];
            }
        }
        //第二部 遍历数据 生成树状结构
        $tree = [];
        foreach ($items as $key => $value) {
            $pid = $parents[$value['value']] ?? 0;
            //如果pid这个节点存在
            if (isset($items[$pid])) {
                $items[$pid]['options'][] = &$items[$key];
                if (in_array($key, $checked_arr)) {
                    $items[$pid]['checkList'][] = $key;
                    $items[$pid]['isIndeterminate'] = true;
                    if (count($items[$pid]['checkList']) - 1 == count($items[$pid]['options'])) {
                        $items[$pid]['checkAll'] = true;
                        $items[$pid]['isIndeterminate'] = false;
                    }
                }
            } else {
                $tree[] = &$items[$key];
            }
        }

        return $tree;
    }
}

if (!function_exists('data_desensitization')) {
    /**
     * 数据脱敏
     *
     * @param string $string       需要脱敏值
     * @param int    $first_length 保留前n位
     * @param int    $last_length  保留后n位
     * @param string $re           脱敏替代符号
     *
     * @return bool|string
     * 例子:
     * data_desensitization('18811113683', 3, 4); //188****3683
     * data_desensitization('王富贵', 0, 1); //**贵
     */
    function data_desensitization($string, $first_length = 0, $last_length = 0, $re = '*')
    {
        if (empty($string) || $first_length < 0 || $last_length < 0) {
            return $string;
        }
        $str_length = mb_strlen($string, 'utf-8');
        $first_str = mb_substr($string, 0, $first_length, 'utf-8');
        $last_str = mb_substr($string, -$last_length, $last_length, 'utf-8');
        if ($str_length <= 2 && $first_length > 0) {
            $replace_length = $str_length - $first_length;

            return $first_str . str_repeat($re, $replace_length > 0 ? $replace_length : 0);
        } elseif ($str_length <= 2 && $first_length == 0) {
            $replace_length = $str_length - $last_length;

            return str_repeat($re, $replace_length > 0 ? $replace_length : 0) . $last_str;
        } elseif ($str_length > 2) {
            $replace_length = $str_length - $first_length - $last_length;

            return $first_str . str_repeat("*", $replace_length > 0 ? $replace_length : 0) . $last_str;
        }
        if (empty($string)) {
            return $string;
        }
    }
}

if (!function_exists('yuan2fen')) {
    /**
     * 转换价格到元, 保留 2 位小数
     *
     * @param $yuan
     *
     * @return string
     */
    function yuan2fen($yuan)
    {
        return (int)round((float)$yuan * 100);
    }
}

if (!function_exists('fen2yuan')) {
    /**
     * 转换价格到分
     *
     * @param $fen
     *
     * @return int
     */
    function fen2yuan($fen)
    {
        return sprintf('%.2f', (int)$fen / 100);
    }
}

// 请谨慎使用该函数，确保输入的目录的正确性
if (!function_exists('rmdir_recursive')) {
    function rmdir_recursive($dir)
    {
        if (!$dir || $dir === '/' || $dir === '.') {
            return false;
        }
        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }
}

if (!function_exists('get_img_ratio')) {
    function get_img_ratio($img)
    {
        [$width, $height] = getimagesize($img);

        return number_format($width / $height, 2);
    }
}

if (!function_exists('encrypt')) {
    function encrypt($txt, $key = 'mengtui')
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
        //$nh = rand(0,64);
        $nh = strlen($txt) % 65;
        $ch = $chars[$nh];
        $mdKey = md5($key . $ch);
        $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
        $txt = base64_encode($txt);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = ($nh + strpos($chars, $txt[$i]) + ord($mdKey[$k++])) % 64;
            $tmp .= $chars[$j];
        }

        return str_replace(['+', '='], ['_', '.'], $ch . $tmp);
    }
}

if (!function_exists('decrypt')) {
    function decrypt($txt, $key = 'mengtui')
    {
        $txt = str_replace(['_', '.'], ['+', '='], $txt);
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
        $ch = $txt[0];
        $nh = strpos($chars, $ch);
        $mdKey = md5($key . $ch);
        $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
        $txt = substr($txt, 1);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = strpos($chars, $txt[$i]) - $nh - ord($mdKey[$k++]);
            while ($j < 0) {
                $j += 64;
            }
            $tmp .= $chars[$j];
        }

        return base64_decode($tmp);
    }
}

if (!function_exists('is_valid_date')) {
    function is_valid_date($date_str)
    {
        return $date_str !== '0000-00-00 00:00:00';
    }
}

if (!function_exists('str_var_replace')) {
    function str_var_replace($str, $data)
    {
        preg_match_all('/{([\s\S]*?)}/', $str, $match);
        $values = [];
        $vars = [];
        foreach (($match && $match[1] ? $match[1] : []) as $item) {
            $vars[] = '{' . $item . '}';
            $values[$item] = Arr::get($data, $item);
        }

        return str_replace($vars, $values, $str);
    }
}

if (!function_exists('convert_memory')) {
    function convert_memory($size)
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}

if (!function_exists('read_file')) {
    function read_file($file)
    {
        $f = fopen($file, 'r');
        try {
            while ($line = fgets($f)) {
                yield $line;
            }
        } finally {
            is_resource($f) && fclose($f);
        }
    }
}

if (!function_exists('get_extension')) {
    function get_extension($file)
    {
        return substr(strrchr($file, '.'), 1);
    }
}

if (!function_exists('get_class_method_params_name')) {
    function get_class_method_params_name($object, $method)
    {
        $ref = new \ReflectionMethod($object, $method);
        $params_name = [];
        foreach ($ref->getParameters() as $item) {
            $params_name[] = $item->getName();
        }

        return $params_name;
    }
}

if (!function_exists('tree_2_paths')) {
    function tree_2_paths($tree, $pre_key = '', $id_key = 'value', $children_key = 'children')
    {
        $arr_paths = [];
        foreach ($tree as $node) {
            $now_key = $pre_key ? $pre_key . '-' . $node[$id_key] : $node[$id_key];
            if (!empty($node['children']) && is_array($node['children'])) {
                $arr = tree_2_paths($node['children'], $now_key, $id_key, $children_key);
                $arr_paths = array_merge($arr_paths, $arr);
            } else {
                $arr_paths[$now_key] = $node[$id_key];
            }
        }

        return $arr_paths;
    }
}

if (!function_exists('getFilePath')) {
    /**
     * 生成csv文件的路径名,如果文件夹不存在,生成相应路径文件夹
     *
     * @param int    $id            如marketing_task_id,user_group_id
     * @param string $relative_path 生成的文件相对路径
     * @param int    $mode          生成的文件目录的权限
     * @param string $type_name     生成的文件的类型名,作更详细的区分
     * @param bool   $is_file_name  是否需要返回文件名
     * @param string $filename      文件名
     *
     * @return mixed  组装好的文件绝对路径
     */
    function getFilePath($id, $relative_path = '/runtime', $mode = 0755, $type_name = '', $is_file_name = false, $filename = '')
    {
        $env = config('app_env');
        $filename = $filename ?: sprintf('%s_%s_%s_%s.csv', date('YmdHis'), $env, $id, $type_name);
        $path = BASE_PATH . $relative_path;
        if ((!file_exists($path)) && (!mkdir($path, $mode, true))) {
            return false;
        }
        if ($is_file_name) {
            return ['filename' => $filename, 'path' => $path . '/' . $filename];
        }

        return $path . '/' . $filename;
    }
}

if (!function_exists('http_build_url')) {
    function http_build_url($url_arr)
    {
        $new_url = $url_arr['scheme'] . "://" . $url_arr['host'];
        if (!empty($url_arr['port'])) {
            $new_url = $new_url . ":" . $url_arr['port'];
        }
        $new_url = $new_url . $url_arr['path'];
        if (!empty($url_arr['query'])) {
            $new_url = $new_url . "?" . $url_arr['query'];
        }
        if (!empty($url_arr['fragment'])) {
            $new_url = $new_url . "#" . $url_arr['fragment'];
        }

        return $new_url;
    }
}

if (!function_exists('replace_url_query')) {
    function replace_url_query($url, array $query)
    {
        $parse = parse_url($url);
        parse_str($parse['query'], $p);
        $parse['query'] = urldecode(http_build_query(array_merge($p, $query)));

        return http_build_url($parse);
    }
}

if (!function_exists('container')) {
    function container(string $id = '')
    {
        $container = ApplicationContext::getContainer();
        if (!$id) {
            return $container;
        }

        return $container->get($id);
    }
}

if (!function_exists('id_gen')) {
    function id_gen()
    {
        return container(IdGeneratorInterface::class)->generate();
    }
}

if (!function_exists('id_degen')) {
    function id_degen(int $id)
    {
        return container(IdGeneratorInterface::class)->degenerate($id);
    }
}

if (!function_exists('format_time')) {
    function format_time($time)
    {
        $output = '';
        foreach (
            [
                86400 => '天',
                3600 => '小时',
                60 => '分',
                1 => '秒',
            ] as $key => $value
        ) {
            if ($time >= $key) {
                $output .= floor($time / $key) . $value;
            }
            $time %= $key;
        }

        return $output;
    }
}

if (!function_exists('my_json_decode')) {
    function my_json_decode($json, $default = [])
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
}

if (!function_exists('is_real_array')) {
    /**
     * 检测是否是一个真实的类C的索引数组
     */
    function is_real_array($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        $n = count($arr);
        for ($i = 0; $i < $n; $i++) {
            if (!isset($arr[$i])) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('is_map_array')) {
    /**
     * 关联数组
     */
    function is_map_array($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        $keys = array_keys($arr);
        foreach ($keys as $item) {
            if (is_numeric($item)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('get_millisecond')) {
    function get_millisecond()
    {
        return round(microtime(true) * 1000);
    }
}

if (!function_exists('is_production')) {
    function is_production()
    {
        $env = env('ENV', '');

        return $env == 'production' || $env == 'prod';
    }
}

if (!function_exists('is_staging')) {
    /**
     * @return bool
     */
    function is_staging()
    {
        $env = env('ENV', '');

        return $env == 'staging' || $env == 'pre';
    }
}

if (!function_exists('is_test')) {
    function is_test()
    {
        return env('ENV') == 'test';
    }
}

if (!function_exists('is_dev')) {
    function is_dev()
    {
        return env('ENV', env('APP_ENV')) == 'dev';
    }
}

if (!function_exists('generate_random_str')) {
    function generate_random_str($length = 12, $prefix = '')
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_str = '';
        for ($i = 0; $i < $length; $i++) {
            $random_str .= $characters[rand(0, strlen($characters) - 1)];
        }

        return trim($prefix) . $random_str;
    }
}

if (!function_exists('get_dir_filename')) {
    function get_dir_filename($dir, $extension = '', $full_path = false)
    {
        $handler = opendir($dir);
        $files = [];
        while (($filename = readdir($handler)) !== false) {
            $filter_extension = $extension === '' ? true : strpos($filename, $extension);
            if (!($filename !== "." && $filename !== ".." && $filter_extension)) {
                continue;
            }
            if ($full_path) {
                $files[] = realpath($dir) . '/' . $filename;
            } else {
                $files[] = $filename;
            }
        }
        closedir($handler);

        return $files;
    }
}

if (!function_exists('sp_encrypt')) {
    function sp_encrypt($plaintext, $key)
    {
        $key = substr(sha1($key, true), 0, 16);
        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $ciphertext_base64 = urlsafe_b64encode($iv . $ciphertext);

        return $ciphertext_base64;
    }
}

if (!function_exists('sp_decrypt')) {
    function sp_decrypt($ciphertext_base64, $key)
    {
        $key = substr(sha1($key, true), 0, 16);
        $ciphertext_dec = urlsafe_b64decode($ciphertext_base64);
        $iv_size = 16;
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $plaintext_dec = openssl_decrypt($ciphertext_dec, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv_dec);

        return $plaintext_dec;
    }
}

if (!function_exists('urlsafe_b64encode')) {
    function urlsafe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace([
            '+',
            '/',
            //        '=',
        ], [
            '-',
            '_',
            //        '',
        ], $data);

        return $data;
    }
}

if (!function_exists('urlsafe_b64decode')) {
    function urlsafe_b64decode($string)
    {
        $data = str_replace([
            '-',
            '_',
        ], [
            '+',
            '/',
        ], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return base64_decode($data);
    }
}

if (!function_exists('csv_big_num')) {
    function csv_big_num($num)
    {
        if (!is_numeric($num)) {
            return $num;
        }

        return "{$num}\t";
    }
}

if (!function_exists('num_zone')) {
    function num_zone($num1, $num2)
    {
        $min = min($num1, $num2);
        $max = max($num1, $num2);

        return $max == $min ? $min : ($min . ' ~ ' . $max);
    }
}

if (!function_exists('logger')) {
    function logger()
    {
        return container(LoggerFactory::class);
    }
}

if (!function_exists('request')) {
    function request()
    {
        return container(RequestInterface::class);
    }
}

if (!function_exists('response')) {
    function response()
    {
        return container(ResponseInterface::class);
    }
}

if (!function_exists('cookie')) {
    /**
     * 快捷方式，返回 request 相关 cookie
     *
     * @param string $key
     *
     * @return mixed
     */
    function cookie(string $key = '')
    {
        if (!ApplicationContext::hasContainer()) {
            return [];
        }
        $cookies = container(RequestInterface::class)->getCookieParams();
        if (empty($key)) {
            return $cookies;
        }

        return $cookies[$key] ?? '';
    }
}

if (!function_exists('request_header')) {
    /**
     * 快捷方式，返回 request 相关 header
     *
     * @param string $key
     *
     * @return mixed
     */
    function request_header(string $key = '')
    {
        if (!ApplicationContext::hasContainer()) {
            return [];
        }
        $headers = container(RequestInterface::class)->getHeaders();
        if (empty($key)) {
            return $headers;
        }

        return $headers[$key] ?? '';
    }
}

if (!function_exists('download')) {
    /**
     * 下载文件
     *
     * @param string $url
     * @param string $file_path
     * @param array  $http_options
     *
     * @return bool
     */
    function download($url, $file_path, $http_options = [])
    {
        try {
            $client = make(Client::class);
            $client->get($url, array_merge([
                'verify' => false,
                'decode_content' => false,
                'timeout' => 600,
                'sink' => $file_path,
            ], $http_options));

            return file_exists($file_path);
        } catch (\Throwable $exception) {
            logger()->get('download')->error((string)$exception);

            return false;
        }
    }
}

if (!function_exists('runtime_path')) {
    function runtime_path($path)
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('is_cli')) {
    /**
     * 判断是否是命令行环境
     *
     * @return bool
     */
    function is_cli()
    {
        return PHP_SAPI === 'cli';
    }
}

if (!function_exists('xml2array')) {
    function xml2array($xml_string, $key = '')
    {
        if (strpos($xml_string, '<') === false) {
            return [];
        }
        $array = (array)@simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$key) {
            return $array;
        }

        return array_get_node($key, $array);
    }
}

if (!function_exists('get_week_day_by_timestamp')) {
    //获取星期几
    function get_week_day_by_timestamp($timestamp)
    {
        if (!$timestamp) {
            return '';
        }
        static $weeks = [
            '天',
            '一',
            '二',
            '三',
            '四',
            '五',
            '六',
        ];

        return '星期' . $weeks[date('w', $timestamp)];
    }
}

if (!function_exists('now')) {
    /**
     * 获取当前时间
     *
     * @param string $format
     *
     * @return false|string
     */
    function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }
}

if (!function_exists('is_json_str')) {
    function is_json_str($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
