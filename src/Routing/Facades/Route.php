<?php

namespace Horizom\Routing\Facades;

use Horizom\Routing\RouteCollector;
use Horizom\Routing\Route as RouteInstance;

/**
 * @method static RouteInstance get(string $path, $handler)
 * @method static RouteInstance post(string $path, $handler)
 * @method static RouteInstance put(string $path, $handler)
 * @method static RouteInstance delete(string $path, $handler)
 * @method static RouteInstance patch(string $path, $handler)
 * @method static RouteInstance head(string $path, $handler)
 * @method static RouteInstance options(string $path, $handler)
 * @method static RouteInstance any(string $path, $handler)
 * @method static RouteInstance map(array $methods, string $path, $handler)
 * @method static RouteInstance group(array $attributes, callable $callback)
 * @method static RouteInstance middleware($middleware)
 * @method static RouteInstance name(string $name)
 * @method static RouteInstance where(array $where)
 * @method static RouteInstance attribute(string $name, $value)
 * @method static RouteInstance attributes(array $attributes)
 * @method static RouteInstance domain(string $domain)
 */
class Route
{
    /**
     * @return RouteCollector
     */
    public static function getInstance(): RouteCollector
    {
        return app()->make(RouteCollector::class);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return RouteInstance
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return self::getInstance()->$method(...$arguments);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return RouteInstance
     */
    public function __call(string $method, array $arguments)
    {
        return self::getInstance()->$method(...$arguments);
    }
}