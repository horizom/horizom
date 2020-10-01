<?php

namespace Horizom\Http;

use Aura\Router\Map;
use Aura\Router\Route as BaseRoute;

class Route
{
    /**
     * A route map.
     *
     * @var Map
     */
    protected static $map;

    /**
     * Get a collection of route objects.
     */
    public static function map(Map $map)
    {
        self::$map = $map;
    }

    /**
     * Adds a generic route.
     */
    public static function route(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->route($name, $path, $handler);
    }

    /**
     * Adds a GET route.
     */
    public static function get(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->get($name, $path, $handler);
    }

    /**
     * Adds a POST route.
     */
    public static function post(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->post($name, $path, $handler);
    }

    /**
     * Adds a DELETE route.
     */
    public static function delete(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->delete($name, $path, $handler);
    }

    /**
     * Adds an OPTIONS route.
     */
    public static function options(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->options($name, $path, $handler);
    }

    /**
     * Adds a PATCH route.
     */
    public static function patch(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->patch($name, $path, $handler);
    }

    /**
     * Adds a PUT route.
     */
    public static function put(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->put($name, $path, $handler);
    }

    /**
     * Adds a HEAD route.
     */
    public static function head(string $name, string $path, $handler): BaseRoute
    {
        return self::$map->head($name, $path, $handler);
    }

    /**
     * Attaches routes to a specific path prefix, and prefixes the attached route names.
     * 
     * @param string $namePrefix — The prefix for all route names being attached.
     * @param string $pathPrefix — The prefix for all route paths being attached.
     * @param callable $callable A callable that uses the Map to add new routes. Its signature is function (\Aura\Router\Map $map); $this Map instance will be passed to the callable.
     */
    public static function attach(string $namePrefix, string $pathPrefix, callable $callable)
    {
        self::$map->attach($namePrefix, $pathPrefix, $callable);
    }
}
