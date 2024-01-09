<?php

declare(strict_types=1);

namespace App\Providers;

use Horizom\Core\ServiceProvider;
use Horizom\Routing\RouteCollector as Router;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $routeMiddlewares = $this->getRouteMiddlewares();

        $this->app->router->group([
            'middleware' => $routeMiddlewares['web'],
        ], fn(Router $router) => __DIR__ . '/../../routes/web.php');

        $this->app->router->group([
            'prefix' => 'api',
            'middleware' => $routeMiddlewares['api'],
        ], fn(Router $router) => base_path('routes/api.php'));
    }

    /**
     * Get all route's middleware
     *
     * @return array
     */
    private function getRouteMiddlewares()
    {
        return [
            'web' => [],
            'api' => [],
        ];
    }
}
