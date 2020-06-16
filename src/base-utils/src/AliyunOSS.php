<?php
namespace HyperfAdmin\BaseUtils;

use OSS\OssClient;

class AliyunOSS
{
    public $access_key;

    public $access_key_secret;

    public $endpoint;

    public $bucket;

    public $host;

    public $cdn;

    public $default_ttl = 60;

    public $default_bytes = 1048576; // 默认1M

    public $max_bytes = 10485760; // 最大10M

    /** @var OssClient */
    public $client;

    const ACL_PRIVATE = 'private';

    public function __construct($bucket = 'default')
    {
        $config = config('storager.' . $bucket);
        $this->access_key = $config['access_key'];
        $this->access_key_secret = $config['access_key_secret'];
        $this->endpoint = $config['endpoint'];
        $this->bucket = $config['bucket'];
        $this->host = $config['host'];
        $this->cdn = $config['cdn'];
        $this->client = new OssClient($this->access_key, $this->access_key_secret, $this->endpoint);
    }

    /**
     * @param string $object
     * @param string $file 本地文件名, 包含完整路径
     *
     * @throws \OSS\Core\OssException
     */
    public function uploadFile($object, $file, $options = [])
    {
        return $this->client->uploadFile($this->bucket, $object, $file, $options);
    }

    public function uploadPrivateFile($object, $file_path, $options = [])
    {
        $ok = $this->uploadFile($object, $file_path, $options);
        if(!$ok) {
            return false;
        }

        return $this->setAcl($object, self::ACL_PRIVATE);
    }

    public function setAcl($object, $acl = 'default')
    {
        if($acl !== 'default') {
            return $this->client->putObjectAcl($this->bucket, $object, $acl);
        }

        return true;
    }

    public function getSignUrl($object, $timeout = 60)
    {
        return $this->client->signUrl($this->bucket, $object, $timeout);
    }

    /**
     * 下载到本地文件
     *
     * @link https://help.aliyun.com/document_detail/88494.html
     * 直接获取文件内容
     * @link https://help.aliyun.com/document_detail/88495.html
     *
     * @param string $object
     * @param string $file 本地文件名, 包含完整路径, 不传则直接获取文件内容
     *
     * @throws \OSS\Core\OssException
     */
    public function getFile($object, $file = null)
    {
        $options = [];
        if($file) {
            $options = [
                OssClient::OSS_FILE_DOWNLOAD => $file,
            ];
        }

        return $this->client->getObject($this->bucket, $object, $options);
    }

    private function gmtISO8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);

        return $expiration . "Z";
    }

    public function getPolicy($config = [])
    {
        $expire = $config['expire'] ?? $this->default_ttl;
        $max_size = $config['max_size'] ?? $this->default_bytes;
        $dir = $config['dir'] ?? '';
        $end = time() + $expire;
        $expiration = $this->gmtISO8601($end);
        //最大文件大小.用户可以自己设置
        $conditions[] = [
            'content-length-range',
            0,
            $max_size,
        ];
        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        if($dir) {
            $conditions[] = [
                'starts-with',
                '$key',
                rtrim($dir, '/') . '/',
            ];
        }
        $base64_policy = base64_encode(json_encode([
            'expiration' => $expiration,
            'conditions' => $conditions,
        ]));
        $signature = base64_encode(hash_hmac('sha1', $base64_policy, $this->access_key_secret, true));

        return [
            'OSSAccessKeyId' => $this->access_key,
            'host' => 'http://' . $this->host,
            'policy' => $base64_policy,
            'Signature' => $signature,
            'expire' => $end,
            'dir' => $dir, //这个参数是设置用户上传指定的前缀
            'cdn' => $this->cdn ? rtrim($this->cdn, '/') : '',
        ];
    }
}
