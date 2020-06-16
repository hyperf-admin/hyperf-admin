<?php

use Hyperf\HttpServer\Router\Router;
use HyperfAdmin\CronCenter\Controller\CronNodeController;
use HyperfAdmin\CronCenter\Controller\CrontabController;

// cron-center routes
register_route('/crontab', CrontabController::class, function ($controller) {
    Router::post('/trigger/{id:\d+}', [$controller, 'trigger']);
});
register_route('/cronnodes', CronNodeController::class, function ($controller) {
    Router::post('/block/{id:\d+}', [$controller, 'block']);
});
