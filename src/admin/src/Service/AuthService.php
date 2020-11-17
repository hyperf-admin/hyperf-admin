<?php
namespace HyperfAdmin\Admin\Service;

use Hyperf\Utils\Arr;
use Hyperf\Utils\Context;
use HyperfAdmin\BaseUtils\Log;
use HyperfAdmin\BaseUtils\Redis\Redis;
use HyperfAdmin\BaseUtils\JWT;

class AuthService
{
    public function check()
    {
        $token = cookie(config('admin_cookie_name', '')) ?: (request_header('x-token')[0] ?? '');
        $payload = JWT::verifyToken($token);
        if(!is_production()) {
            Log::get('userinfo')->info('hyperf_admin_token_payload', is_array($payload) ? $payload : [$payload]);
        }
        if(!$payload) {
            return [];
        }
        $sso_user_info = Arr::get($payload, 'user_info');
        if(empty($sso_user_info)) {
            return [];
        }
        $cache_key = config('user_info_cache_prefix') . md5(json_encode($sso_user_info));
        if(!$user = Redis::get($cache_key)) {
            $user = container(UserService::class)->findUserOrCreate($sso_user_info);
            $expire = $payload['exp'] - time();
            if($user && $expire > 0) {
                // 缓存用户信息
                Redis::setex($cache_key, $expire, json_encode($user));
            }
        } else {
            $user = json_decode($user, true);
        }

        return $this->setUser($user);
    }

    public function user()
    {
        return Context::get('user_info') ?? [];
    }

    public function isSupperAdmin()
    {
        $user = $this->user();

        return ($user['is_admin'] ?? NO) === YES;
    }

    public function setUser($data)
    {
        return Context::set('user_info', $data);
    }

    public function get($key)
    {
        return Arr::get($this->user(), $key);
    }

    public function logout()
    {
        $token = cookie(config('admin_cookie_name', '')) ?: (request_header('x-token')[0] ?? '');
        $payload = JWT::verifyToken($token);
        $user = Arr::get($payload, 'user_info');
        $cache_key = config('user_info_cache_prefix') . md5(json_encode($user));
        Redis::del($cache_key);
        Context::set('user_info', null);
    }
}
