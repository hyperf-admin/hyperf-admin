<?php

use Hyperf\HttpServer\Router\Router;

// 健康检查
Router::get('/ping', function () {
    return [
        'code' => 200,
        'message' => 'ok',
    ];
});
