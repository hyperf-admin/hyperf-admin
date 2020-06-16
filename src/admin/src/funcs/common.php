<?php

use HyperfAdmin\Admin\Service\OperatorLogService;
use HyperfAdmin\Admin\Service\AuthService;
use HyperfAdmin\Admin\Service\PermissionService;

if (!function_exists('log_operator')) {
    function log_operator($model, $action, $ids, $remark = '', $options = [], $user_id = 0)
    {
        return make(OperatorLogService::class)->write($model, $action, $ids, $remark, $options, $user_id);
    }
}

if (! function_exists('auth')) {
    function auth(?string $field = null)
    {
        $auth = container(AuthService::class);
        if (is_null($field)) {
            return $auth;
        }
        return $auth->get($field);
    }
}

if (! function_exists('permission')) {
    function permission(?string $uri = null, string $method = 'POST')
    {
        $permission = make(PermissionService::class);
        if (is_null($uri)) {
            return $permission;
        }
        return $permission->can($uri, strtoupper($method));
    }
}
