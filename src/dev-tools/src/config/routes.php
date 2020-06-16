<?php

use Hyperf\HttpServer\Router\Router;
use HyperfAdmin\DevTools\Controller\DevController;

// dev-tools routes

register_route('/dev', DevController::class, function ($controller) {
    Router::get('/controllermaker', [$controller, 'controllerMaker']);
    Router::get('/dbAct', [$controller, 'dbAct']);
    Router::get('/tableAct', [$controller, 'tableAct']);
    Router::get('/tableSchema', [$controller, 'tableSchema']);
    Router::post('/make', [$controller, 'make']);
});
