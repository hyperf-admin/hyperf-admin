<?php

use Hyperf\HttpServer\Router\Router;
use HyperfAdmin\Admin\Controller\CommonConfigController;
use HyperfAdmin\Admin\Controller\MenuController;
use HyperfAdmin\Admin\Controller\RoleController;
use HyperfAdmin\Admin\Controller\SystemController;
use HyperfAdmin\Admin\Controller\UploadController;
use HyperfAdmin\Admin\Controller\UserController;

Router::addGroup('/upload', function () {
    Router::post('/image', [UploadController::class, 'image']);
    Router::get('/ossprivateurl', [UploadController::class, 'privateFileUrl']);
});

register_route('/user', UserController::class, function ($controller) {
    Router::get('/menu', [$controller, 'menu']);
    Router::get('/roles', [$controller, 'roles']);
    Router::post('/login', [$controller, 'login']);
    Router::post('/logout', [$controller, 'logout']);
    Router::get('/export', [$controller, 'export']);
    Router::get('/exports', [$controller, 'exportTasks']);
    Router::post('/exports/retry/{id:\d+}', [$controller, 'exportTasksRetry']);
    Router::get('/exportLimit', [$controller, 'exportLimit']);
});

register_route('/role', RoleController::class);

register_route('/menu', MenuController::class, function ($controller) {
    Router::get('/tree', [$controller, 'menuTree']);
    Router::get('/getOpenApis', [$controller, 'getOpenApis']);
    Router::post('/permission/clear', [$controller, 'clearPermissionCache']);
});

register_route('/cconf', CommonConfigController::class, function ($controller) {
    Router::get('/detail/{key:[a-zA-Z-_0-1]+}', [$controller, 'detail']);
    Router::post('/detail/{key:[a-zA-Z-_0-1]+}', [$controller, 'saveDetail']);
});

Router::get('/system/config', [SystemController::class, 'config']);
Router::get('/system/routes', [SystemController::class, 'routes']);
Router::get('/system/proxy', [SystemController::class, 'proxy']);
Router::get('/system/list_info/{id:\d+}', [SystemController::class, 'listInfo']);
Router::get('/system/list/{id:\d+}', [SystemController::class, 'listDetail']);
Router::get('/system/form/{route_id:\d+}/form', [SystemController::class, 'formInfo']);
Router::get('/system/form/{route_id:\d+}/{id:\d+}', [SystemController::class, 'formInfo']);
Router::post('/system/form/{route_id:\d+}/{id:\d+}', [SystemController::class, 'formSave']);
