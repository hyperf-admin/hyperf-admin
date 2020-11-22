<?php
namespace HyperfAdmin\BaseUtils;

use GuzzleHttp\Client;
use Hyperf\Guzzle\ClientFactory;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Cookie\CookieJar;

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

    public static function proxy($url, ServerRequestInterface $request)
    {
        $client = self::create([
            'timeout' => 10.0,
        ]);

        $options = [];

        $logger = Log::get('module_proxy');

        try {
            $options['headers']['X-No-Proxy'] = true;

            $options['headers'] = array_merge($options['headers'], array_map(function ($item) {
                return $item[0];
            }, request_header()));

            foreach ($options['headers'] as $key => $val) {
                $new_key = implode('-', array_map('ucfirst', explode('-', $key)));
                $options['headers'][$new_key] = $val;
                unset($options['headers'][$key]);
            }

            $parse =parse_url($url);
            $domain = isset($parse['port']) ? $parse['host'] . ':' . $parse['port'] : $parse['host'];
            $options['cookies'] =  CookieJar::fromArray(cookie(), $domain);

            if ($query = $request->getQueryParams()) {
                $options['query'] = $query;
            }
            if ($body =  (array)json_decode($request->getBody()->getContents(), true)) {
                $options['body'] = \GuzzleHttp\json_encode($body);
            }
            if ($form_data = $request->getParsedBody()) {
                $options['form_params'] = $form_data;
            }

            $request = retry(3, function () use ($client, $request, $url, $options) {
                return $client->request($request->getMethod(), $url, $options);
            }, 50);
            $content = $request->getBody()->getContents();
            $logger->info('proxy_success', compact('url', 'options'));
            return my_json_decode($content);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $logger->error('proxy_fail', compact('url', 'options', 'e'));
            throw new \Exception("proxy exception {$e}", 500);
        } catch (\Throwable $e) {
            $logger->error('proxy_fail', compact('url', 'options', 'e'));
            throw new \Exception("proxy exception {$e}", 500);
        }
    }
}
