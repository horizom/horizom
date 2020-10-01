<?php

use Horizom\Config\Config;
use Horizom\Core\App;
use Horizom\Http\Request;

if (!function_exists('void_class')) {
    /**
     * Permet de créer une class et de la remplir avec un tableau associatif
     */
    function void_class(array $array = [])
    {
        $class = new stdClass;

        foreach ($array as $key => $value) {
            $class->$key = $value;
        }

        return $class;
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for an asset using the current scheme of the request (HTTP or HTTPS).
     */
    function url(string $path = null)
    {
        $base_url = trim(Request::fromInstance()->baseUrl(), '/');
        return ($path) ? $base_url . '/' . $path : $base_url;
    }
}

if (!function_exists('config')) {
    /**
     * Accessing Configuration Values
     */
    function config(string $key, $default = null)
    {
        $settings = App::config();
        $config = new Config($settings);

        return isset($config[$key]) ? $config[$key] : $default;
    }
}
