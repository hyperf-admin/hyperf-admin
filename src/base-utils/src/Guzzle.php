<?php
namespace HyperfAdmin\BaseUtils;

use GuzzleHttp\Client;
use Hyperf\Guzzle\ClientFactory;

class Guzzle
{
    /**
     * @param array $config
     *
     * @return Client
     */
    public static function create(array $config = [])
    {
        // 如果在协程环境下创建，则会自动使用协程版的 Handler，非协程环境下无改变
        return container(ClientFactory::class)->create($config);
    }

    public static function get($url, $query = [], $header = [])
    {
        return self::request('get', $url, $query, $header);
    }

    public static function post($url, $params = [], $header = [])
    {
        return self::request('post', $url, $params, $header);
    }

    public static function request($method, $api, $params = [], $headers = [])
    {
        if(!$api) {
            Log::get('api_request')->warning('api is empty');

            return false;
        }
        $client = self::create([
            'timeout' => $headers['timeout'] ?? 10.0,
        ]);
        $method = strtoupper($method);
        $options = [];
        $headers['charset'] = $headers['charset'] ?? 'UTF-8';
        $options['headers'] = $headers;
        if($method == 'GET' && $params) {
            $options['query'] = $params;
        }
        if($method == 'POST') {
            $options['headers']['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
            if($options['headers']['Content-Type'] == 'application/json' && $params) {
                $options['body'] = \GuzzleHttp\json_encode($params ? $params : (object)[]);
            }
            if($options['headers']['Content-Type'] == 'application/x-www-form-urlencoded' && $params) {
                $options['form_params'] = $params;
            }
        }
        try {
            $request = $client->request($method, $api, $options);
            $code = $request->getStatusCode();
            $content = $request->getBody()->getContents();
            $content = my_json_decode($content);
            Log::get('api_request')->info($api, [
                'method' => $method,
                'code' => $code,
                'options' => $options,
                'content' => $content,
            ]);

            return $content;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            Log::get('api_request')->error($api, [
                'method' => $method,
                'options' => $options,
                'exception' => (string)$e,
            ]);

            return false;
        }
    }
}
