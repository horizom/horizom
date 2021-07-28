<?php

use Horizom\App;
use Horizom\Http\Response;
use Illuminate\Support\Facades\Hash;

if (!function_exists('url')) {
    /**
     * Generate a URL.
     */
    function url(string $path = null)
    {
        $base_url = trim(HORIZOM_BASE_URL, '/');
        return ($path) ? $base_url . '/' . trim($path, '/') : $base_url;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL for an asset using the current scheme of the request (HTTP or HTTPS).
     */
    function asset(string $path = null)
    {
        $base_url = trim(HORIZOM_BASE_URL, '/');
        return ($path) ? $base_url . '/' . $path : $base_url;
    }
}

if (!function_exists('config')) {
    /**
     * Accessing Configuration Values
     */
    function config(string $key, $default = null)
    {
        $configs = App::config();
        return isset($configs[$key]) ? $configs[$key] : $default;
    }
}

if (!function_exists('response')) {
    /**
     * Returning a full Response instance
     * 
     * Allows you to customize the response's HTTP status code and headers
     */
    function response(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        return new Response($status, $headers, $body, $version, $reason);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to specified location
     *
     * This function prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param string    $url The redirect destination.
     * @param int|null  $status The redirect HTTP status code.
     */
    function redirect(string $url, ?int $status = null)
    {
        return (new Response())->redirect($url, $status);
    }
}

if (!function_exists('view')) {
    /**
     * Return a view as the response's content
     */
    function view(string $name, array $data = [])
    {
        return (new Response())->view($name, $data);
    }
}

if (!function_exists('debug')) {
    /**
     * var_dump & die
     */
    function debug($var)
    {
        var_dump($var);
        die;
    }
}

if (!function_exists('app')) {
    /**
     * Application
     */
    function app()
    {
        return App::getInstance();
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hashes the given value using Bcrypt.
     */
    function bcrypt(string $value)
    {
        return Hash::make($value);
    }
}
