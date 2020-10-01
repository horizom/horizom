<?php

namespace Horizom\Http;

use Aura\Router\Route as AuraRoute;
use GuzzleHttp\Psr7\ServerRequest as BaseServerRequest;
use Illuminate\Support\Str;
use Horizom\Collection\DataCollection;
use Horizom\Collection\FilesDataCollection;
use Horizom\Collection\ServerDataCollection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends BaseServerRequest implements ServerRequestInterface
{
    /**
     * @var string
     */
    private $base_path;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $full_url;

    /** 
     * @var string
     */
    private $method;

    /** 
     * @var UriInterface 
     */
    private $uri;

    /**
     * @var array
     */
    private $route;

    /**
     * @var DataCollection GET (query) parameters
     */
    private $query;

    /**
     * @var DataCollection POST parameters
     */
    private $post;

    /**
     * @var DataCollection Client cookie data
     */
    private $cookie;

    /**
     * @var ServerDataCollection Server created attributes
     */
    private $server;

    /**
     * @var FilesDataCollection Uploaded temporary files
     */
    private $files;

    /**
     * Horizom\Http\Response
     */
    public function __construct($method, $uri, array $headers = [], $body = null, $version = '1.1', array $serverParams = [])
    {
        parent::__construct($method, $uri, $headers, $body, $version, $serverParams);

        $this->route = [
            'name' => '',
            'controller' => '',
            'action' => ''
        ];

        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if (config('app.base_path') === null) {
            if (isset($_SERVER['PATH_INFO'])) {
                $base_path = str_replace($_SERVER['PATH_INFO'], '', $uri->getPath());
            } else {
                $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
                $appRoot = str_replace('\\', '/', HORIZOM_ROOT);
                $base_path = str_replace($docRoot, '', $appRoot);
            }
        } else {
            $base_path = config('app.base_path');
        }

        $path = trim($base_path, '/');
        $host = $uri->getHost();
        $uri_query = $uri->getQuery();

        if ($uri->getPort()) {
            $host = $host . ':' . $uri->getPort();
        }

        $base_uri = ($path) ? $host . '/' . $path : $host;
        $request_uri = $uri->getPath();
        $queries = $this->parseQuery($uri_query);
        $www = 'www.';

        if (config('system.redirect.www') && substr($base_uri, 0, 4) !== $www) {
            $base_uri = $www . $base_uri;
        }

        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->protocol = $version;
        $this->base_path = $base_path;
        $this->request_path = str_replace($base_path, '', $uri->getPath());
        $this->base_url = $this->uri->getScheme() . '://' . $base_uri;
        $this->full_url = $this->url = $this->uri->getScheme() . '://' . $host . $request_uri;

        if ($uri_query) {
            $this->full_url = $this->full_url . '?' . $uri_query;
        }

        $this->query = new DataCollection($queries);
        $this->post = new DataCollection($_POST);
        $this->cookie = new DataCollection($_COOKIE);
        $this->files = new FilesDataCollection($_FILES);
        $this->server = new ServerDataCollection($_SERVER);
    }

    /**
     * Return a Request
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return Request
     */
    public static function fromInstance()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        return new Request($method, $uri, $headers, null, $protocol, $_SERVER);
    }

    /**
     * Return the request's path information
     */
    public function path()
    {
        return $this->getUri()->getPath();
    }

    /**
     * Return the HTTP verb for the request.
     */
    public function method(string $method)
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Access all of the user POST input
     */
    public function post()
    {
        return $this->post;
    }

    /**
     * Access values from entire request payload (including the query string)
     * 
     * @return null
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Access uploaded files from the request
     */
    public function files(string $name)
    {
        return $this->files->row($name);
    }

    /**
     * Access all of the user COOKIE input
     */
    public function cookie()
    {
        return $this->cookie;
    }

    /**
     * Access all server params
     */
    public function server()
    {
        return $this->server;
    }

    /**
     * Determine if a file is present on the request
     */
    public function hasFile(string $name)
    {
        return $this->files->exists($name);
    }

    /**
     * Return the URL without the query string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * Return the URL includes the query string
     */
    public function fullUrl(): string
    {
        return $this->full_url;
    }

    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function is(...$patterns)
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $this->request_path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the current route data
     */
    public function route()
    {
        return $this->route;
    }

    /**
     * Return the base url
     */
    public function baseUrl()
    {
        return $this->base_url;
    }

    /**
     * Return the base path
     */
    public function basePath()
    {
        return $this->base_path;
    }

    /**
     * Parse the request
     */
    public function parseRoute(AuraRoute $route)
    {
        $route_map = explode('@', $route->handler);
        $ctrl_map = array_map('ucfirst', explode('/', $route_map[0]));

        $controller = (count($ctrl_map) > 1) ? implode(DIRECTORY_SEPARATOR, $ctrl_map) : $ctrl_map[0];
        $action = isset($route_map[1]) ? $route_map[1] : 'index';

        $this->route['name'] = $route->name;
        $this->route['controller'] = $controller;
        $this->route['action'] = $action;

        return $this;
    }

    /**
     * @param string $query
     * @return array
     */
    private function parseQuery(string $query)
    {
        $params = [];

        if ($query) {
            foreach (explode('&', $query) as $k => $v) {
                $param = explode('=', $v);
                $params[$param[0]] = $param[1];
            }
        }

        return $params;
    }
}
