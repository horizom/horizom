<?php

declare(strict_types=1);

namespace App\Providers;

use Horizom\Core\ServiceProvider;

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
        ], function () {
            require base_path('routes/web.php');
        });

        $this->app->router->group([
            'prefix' => 'api',
            'middleware' => $routeMiddlewares['api'],
        ], function () {
            require base_path('routes/api.php');
        });
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
