<?php

use Hyperf\HttpServer\Router\Router;
use HyperfAdmin\EventBus\Controller\PushController;

Router::post('/event-bus/push', [PushController::class, 'push']);
