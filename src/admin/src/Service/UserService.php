<?php
namespace HyperfAdmin\Admin\Service;

use HyperfAdmin\Admin\Model\User;

class UserService
{
    public function getUser($filter)
    {
        if (!is_array($filter)) {
            $where = ['id' => (int)$filter];
        } else {
            $where = $filter;
        }
        $user = make(User::class)->where2query($where)->select([
            'id',
            'username',
            'realname',
            'mobile',
            'email',
            'status',
            'is_admin',
            'avatar',
            'roles',
        ])->first();
        if (!$user) {
            return false;
        }

        return $user->toArray();
    }

    public function batchGetUser(array $ids, $status = YES)
    {
        $users = User::query()->whereIn('id', $ids)->where('status', $status)->select([
            'id',
            'username',
            'realname',
            'mobile',
            'email',
            'status',
            'is_admin',
            'avatar',
            'roles',
        ])->get();
        if (!$users) {
            return false;
        }

        return $users->toArray();
    }

    public function findUserOrCreate($sso_user_info)
    {
        $where = empty($sso_user_info['mobile']) ? [
            'username' => $sso_user_info['name'],
        ] : [
            'username' => [$sso_user_info['name'], $sso_user_info['mobile']],
        ];
        $user_info = $this->getUser($where);
        if ($user_info) {
            return $user_info;
        }
        $data = [
            'username' => $sso_user_info['mobile'],
            'mobile' => $sso_user_info['mobile'],
            'avatar' => $sso_user_info['avatar'] ?? '',
            'password' => '',
            'status' => YES,
            'realname' => $sso_user_info['name'] ?? '',
            'login_time' => date("Y-m-d H:i:s"),
            'login_ip' => '',
        ];
        $user = new User($data);
        $user->save();

        return $user->toArray();
    }
}
