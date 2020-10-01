<?php

namespace Horizom\Core;

use Jenssegers\Blade\Blade;
use Psr\Http\Message\ResponseInterface;

class Renderer
{
    /**
     * @var Blade
     */
    private static $engine;

    /**
     * @var array
     */
    private static $attributs = [];

    /**
     * Init the render
     */
    public static function init()
    {
        $viewPath = HORIZOM_RESOURCES . "views";
        $cachePath = HORIZOM_RESOURCES . "cache";
        self::$engine = new Blade($viewPath, $cachePath);
    }

    public static function print(ResponseInterface $response)
    {
        echo \GuzzleHttp\Psr7\str($response);
    }

    /**
     * Get view content
     */
    public static function make(string $view, array $data = [])
    {
        self::$attributs = array_merge(self::$attributs, $data);
        return self::$engine->make($view, self::$attributs)->render();
    }
}
