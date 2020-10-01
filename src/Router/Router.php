<?php

namespace Horizom\Router;

use Aura\Router\Map;
use Aura\Router\RouterContainer as Container;
use Horizom\Http\Request;

class Router
{
    /**
     * @var
     */
    private static $container;

    /**
     * A route map.
     *
     * @var Map
     */
    protected static $map;

    /**
     * Init the router
     */
    public static function init(): Map
    {
        if (config("app.base_path") === null) {
            if (isset($_SERVER['PATH_INFO'])) {
                $uri = Request::fromGlobals()->getUri();
                $basepath = str_replace($_SERVER['PATH_INFO'], '', $uri->getPath());
            } else {
                $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
                $appRoot = str_replace('\\', '/', HORIZOM_ROOT);
                $basepath = str_replace($docRoot, '', $appRoot);
            }
        } else {
            $basepath = config("app.base_path");
        }

        self::$container = new Container($basepath);
        self::$map = self::$container->getMap();

        return self::$map;
    }

    /**
     * Get a collection of route objects.
     *
     * @return Container
     */
    public static function container()
    {
        return self::$container;
    }

    /**
     * Get a collection of route objects.
     *
     * @return Map
     */
    public static function map()
    {
        return self::$map;
    }
}
