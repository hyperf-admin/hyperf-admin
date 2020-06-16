<?php

use Hyperf\HttpServer\Router\Router;
use HyperfAdmin\DataFocus\Controller\DsnController;
use HyperfAdmin\DataFocus\Controller\PluginFunctionController;
use HyperfAdmin\DataFocus\Controller\ReportsController;

// data-focus routes
register_route('/reports', ReportsController::class, function ($controller) {
    Router::get('/execute/{id:\d+}', [$controller, 'execute']);
    Router::post('/publish/{id:\d+}', [$controller, 'publish']);
});

register_route('/dsn', DsnController::class);

register_route('/plugin_function', PluginFunctionController::class);
