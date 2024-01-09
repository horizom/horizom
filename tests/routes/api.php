<?php

use App\Controllers\ApiController;
use Horizom\Routing\RouteCollector;

/**
 * @var RouteCollector $router
 */

$router->group([], function (RouteCollector $router) {
    $router->any('/', [ApiController::class, 'index']);
    $router->any('/status', [ApiController::class, 'status']);
    $router->any('/version', [ApiController::class, 'version']);
});
