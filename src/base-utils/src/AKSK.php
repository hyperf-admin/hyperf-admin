<?php
namespace HyperfAdmin\BaseUtils;

/**
 * AKSK 模式鉴权
 */
class AKSK
{
    private $access_key;

    private $secret_key;

    public function __construct($access_key, $secret_key)
    {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
    }

    public function token($method, $path, $host, $query, $content_type, $body)
    {
        $data = '';
        if(!empty($path)) {
            $data = $method . ' ' . $path;
        }
        if(!empty($query)) {
            $data .= '?' . $query;
        }
        $data .= "\nHost: " . $host;
        if(!empty($content_type)) {
            $data .= "\nContent-Type: " . $content_type;
        }
        $data .= "\n\n";
        if(!empty($body)) {
            $data .= $body;
        }
        $sign = $this->sign($this->secret_key, $data);

        return 'ha ' . $this->access_key . ':' . $sign;
    }

    private function digest($secret, $data)
    {
        return hash_hmac('sha1', $data, $secret, true);
    }

    private function sign($secret, $data)
    {
        return urlsafe_b64encode($this->digest($secret, $data));
    }
}
