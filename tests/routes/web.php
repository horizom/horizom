<?php

use App\Controllers\MainController;
use Horizom\Routing\RouteCollector;

/**
 * @var RouteCollector $router
 */

$router->get('/', [MainController::class, 'index']);
