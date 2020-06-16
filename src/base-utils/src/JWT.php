<?php
/**
 * JWT
 */
namespace HyperfAdmin\BaseUtils;

class JWT
{
    //头部
    private static $header = [
        'alg' => 'HS256', //生成signature的算法
        'typ' => 'JWT', //类型
    ];

    //使用HMAC生成信息摘要时所使用的密钥
    private static $key = 'ha-jwt';

    /**
     * 获取jwt token
     *
     * @param array $payload jwt载荷   格式如下非必须
     *                       [
     *                       'iss'=>'jwt_admin',  //该JWT的签发者
     *                       'iat'=>time(),  //签发时间
     *                       'exp'=>time()+7200,  //过期时间
     *                       'nbf'=>time()+60,  //该时间之前不接收处理该Token
     *                       'sub'=>'www.xxx.com',  //面向的用户
     *                       'jti'=>md5(uniqid('JWT').time())  //该Token唯一标识
     *                       ]
     *
     * @return bool|string
     */
    public static function token(array $payload)
    {
        if(!is_array($payload)) {
            return false;
        }
        $base64_header = self::base64UrlEncode(json_encode(self::$header, JSON_UNESCAPED_UNICODE));
        $base64_payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $token = $base64_header . '.' . $base64_payload . '.' . self::signature($base64_header . '.' . $base64_payload, self::$key, self::$header['alg']);

        return $token;
    }

    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     *
     * @param string $token 需要验证的token
     *
     * @return bool|string
     */
    public static function verifyToken(string $token)
    {
        $tokens = explode('.', $token);
        if(count($tokens) != 3) {
            return false;
        }
        [$base64_header, $base64_payload, $sign] = $tokens;
        //获取jwt算法
        $base64_decode_header = json_decode(self::base64UrlDecode($base64_header), true);
        if(empty($base64_decode_header['alg'])) {
            return false;
        }
        //签名验证
        if(self::signature($base64_header . '.' . $base64_payload, self::$key, $base64_decode_header['alg']) !== $sign) {
            return false;
        }
        $payload = json_decode(self::base64UrlDecode($base64_payload), true);
        //签发时间大于当前服务器时间验证失败
        if(isset($payload['iat']) && $payload['iat'] > time()) {
            return false;
        }
        //过期时间小于当前服务器时间验证失败
        if(isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        //该nbf时间之前不接收处理该Token
        if(isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }

        return $payload;
    }

    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     *
     * @param string $input 需要编码的字符串
     *
     * @return string
     */
    private static function base64UrlEncode(string $input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     *
     * @param string $input 需要解码的字符串
     *
     * @return bool|string
     */
    private static function base64UrlDecode(string $input)
    {
        $remainder = strlen($input) % 4;
        if($remainder) {
            $add_len = 4 - $remainder;
            $input .= str_repeat('=', $add_len);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * HMACSHA256签名   https://jwt.io/  中HMACSHA256签名实现
     *
     * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
     * @param string $key
     * @param string $alg   算法方式
     *
     * @return mixed
     */
    private static function signature(string $input, string $key, string $alg = 'HS256')
    {
        $alg_config = [
            'HS256' => 'sha256',
        ];

        return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key, true));
    }
}
