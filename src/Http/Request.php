<?php

namespace Horizom\Http;

use Horizom\Http\Collection\FileCollection;
use Horizom\Http\Collection\ServerCollection;
use Horizom\Http\Exceptions\HttpException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Psr\Http\Message\UriInterface;

final class Request extends \Nyholm\Psr7\ServerRequest
{
    /**
     * @var Collection GET (query) parameters
     */
    private $query;

    /**
     * @var Collection POST parameters
     */
    private $post;

    /**
     * @var Collection Client cookie data
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
     * The route resolver callback.
     *
     * @var \Closure
     */
    protected $routeResolver;

    /**
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI
     * @param array $headers Request headers
     * @param string|resource|StreamInterface|null $body Request body
     * @param string $version Protocol version
     */
    public function __construct($method, $uri, array $headers = [], $body = null, $version = '1.1', array $serverParams = [])
    {
        parent::__construct($method, $uri, $headers, $body, $version, $serverParams);
        $this->initialize($uri);
    }

    /**
     * @param string|UriInterface $uri URI
     */
    private function initialize($uri)
    {
        $basePath = config('app.base_path');
        $path = trim($basePath, '/');
        $host = $uri->getHost();
        $port = $uri->getPort();
        $uri_query = $uri->getQuery();

        if ($port && !in_array($port, [80, 443])) {
            $host = $host . ':' . $port;
        }

        $base_uri = ($path) ? $host . '/' . $path : $host;
        $request_uri = $uri->getPath();
        $queries = $this->parseQuery($uri_query);

        $this->base_path = $basePath;
        $this->request_path = str_replace($basePath, '', $uri->getPath());
        $this->base_url = $uri->getScheme() . '://' . $base_uri;
        $this->full_url = $this->url = $uri->getScheme() . '://' . $host . $request_uri;

        if ($uri_query) {
            $this->full_url = $this->full_url . '?' . $uri_query;
        }

        $this->query = new Collection($queries);
        $this->post = new Collection($_POST);
        $this->cookie = new Collection($_COOKIE);
        $this->files = new FileCollection($_FILES);
        $this->server = new ServerCollection($_SERVER);

        define("HORIZOM_BASE_PATH", $this->base_path);
        define("HORIZOM_BASE_URL", $this->base_url);
    }

    /**
     * Create new Request
     */
    public static function create()
    {
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = fopen('php://input', 'r') ?: null;
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        return new Request($method, $uri, $headers, $body, $protocol, $_SERVER);
    }

    /**
     * Return the request's path information
     */
    public function path()
    {
        return $this->getUri()->getPath();
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method()
    {
        return $this->getMethod();
    }

    /**
     * Get the root URL for the application.
     *
     * @return string
     */
    public function root()
    {
        return rtrim($this->baseUrl(), '/');
    }

    /**
     * Access all of the user POST input
     *
     * @param string $name
     * @return mixed|Collection
     */
    public function post(string $name = null)
    {
        if ($name) {
            return $this->post->get($name);
        }

        return $this->post;
    }

    /**
     * Access values from entire request payload (including the query string)
     *
     * @param string $name
     * @return mixed|Collection
     */
    public function query(string $name = null)
    {
        if ($name) {
            return $this->query->get($name);
        }

        return $this->query;
    }

    /**
     * Access uploaded files from the request
     *
     * @param string $name
     * @return mixed|FileCollection
     */
    public function files(string $name = null)
    {
        if ($name) {
            return $this->files->get($name);
        }

        return $this->files;
    }

    /**
     * Access all of the user COOKIE input
     *
     * @param string $name
     * @return mixed|Collection
     */
    public function cookie(string $name = null)
    {
        if ($name) {
            return $this->cookie->get($name);
        }

        return $this->cookie;
    }

    /**
     * Access all server params
     *
     * @param string $name
     * @return mixed|ServerCollection
     */
    public function server(string $name = null)
    {
        if ($name) {
            return $this->server->get($name);
        }

        return $this->server;
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
     * Get the client user agent.
     *
     * @return string|null
     */
    public function userAgent()
    {
        return $this->getHeader('User-Agent');
    }

    /**
     * Get the route handling the request.
     *
     * @param  string|null  $param
     * @param  mixed  $default
     *
     * @return array|string
     */
    public function route($param = null, $default = null)
    {
        $route = ($this->getRouteResolver())();

        if (is_null($route) || is_null($param)) {
            return $route;
        }

        return Arr::get($route[2], $param, $default);
    }

    /**
     * Get a unique fingerprint for the request / route / IP address.
     *
     * @return string
     * @throws \HttpException
     */
    public function fingerprint()
    {
        if (!$route = $this->route()) {
            throw new HttpException('Unable to generate fingerprint. Route unavailable.');
        }

        return sha1(implode('|', [
            $this->getMethod(), $this->root(), $this->path(), $this->ip(),
        ]));
    }

    /**
     * Get id address
     *
     * @return string|null
     */
    public function ip()
    {
        if ($this->getHeader('http-cf-connecting-ip') !== null) {
            return $this->getHeader('http-cf-connecting-ip');
        }

        if ($this->getHeader('http-x-forwarded-for') !== null) {
            return $this->getHeader('http-x-forwarded_for');
        }

        return $this->getHeader('remote-addr');
    }

    /**
     * Determine if the request is over HTTPS.
     *
     * @return bool
     */
    public function secure()
    {
        return $this->isSecure();
    }

    /**
     * Check if request connection is secure
     */
    public function isSecure()
    {
        return $this->getHeader('http-x-forwarded-proto') === 'https' || $this->getHeader('https') !== null || $this->getHeader('server-port') === 443;
    }

    /**
     * Determine if the request is the result of an AJAX call.
     */
    public function ajax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Determine if the request is the result of an PJAX call.
     */
    public function pjax()
    {
        return $this->getHeader('X-PJAX') == true;
    }

    /**
     * Determine if the request is the result of an AJAX call.
     */
    public function isXmlHttpRequest()
    {
        $isXhr = false;
        $headerX = $this->getHeader('http-x-requested-with');

        foreach ($headerX as $value) {
            $isXhr = strtolower($value) === 'xmlhttprequest';
            break;
        }

        return $isXhr;
    }

    /**
     * Get the route resolver callback.
     *
     * @return \Closure
     */
    public function getRouteResolver()
    {
        return $this->routeResolver ?: function () {
            //
        };
    }

    /**
     * Get a Uri populated with values from $_SERVER.
     */
    public static function getUriFromGlobals(): UriInterface
    {
        $uri = new Uri('');

        $uri = $uri->withScheme(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');

        $hasPort = false;
        if (isset($_SERVER['HTTP_HOST'])) {
            [$host, $port] = self::extractHostAndPortFromAuthority($_SERVER['HTTP_HOST']);
            if ($host !== null) {
                $uri = $uri->withHost($host);
            }

            if ($port !== null) {
                $hasPort = true;
                $uri = $uri->withPort($port);
            }
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $uri = $uri->withHost($_SERVER['SERVER_NAME']);
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $uri = $uri->withHost($_SERVER['SERVER_ADDR']);
        }

        if (!$hasPort && isset($_SERVER['SERVER_PORT'])) {
            $uri = $uri->withPort($_SERVER['SERVER_PORT']);
        }

        $hasQuery = false;
        if (isset($_SERVER['REQUEST_URI'])) {
            $requestUriParts = explode('?', $_SERVER['REQUEST_URI'], 2);
            $uri = $uri->withPath($requestUriParts[0]);
            if (isset($requestUriParts[1])) {
                $hasQuery = true;
                $uri = $uri->withQuery($requestUriParts[1]);
            }
        }

        if (!$hasQuery && isset($_SERVER['QUERY_STRING'])) {
            $uri = $uri->withQuery($_SERVER['QUERY_STRING']);
        }

        return $uri;
    }

    private static function extractHostAndPortFromAuthority(string $authority): array
    {
        $uri = 'http://' . $authority;
        $parts = parse_url($uri);
        if (false === $parts) {
            return [null, null];
        }

        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;

        return [$host, $port];
    }

    /**
     * @param string $query
     * @return array
     */
    private function parseQuery(string $query)
    {
        $params = [];

        if ($query) {
            foreach (explode('&', $query) as $v) {
                $param = explode('=', $v);
                $params[$param[0]] = $param[1];
            }
        }

        return $params;
    }
}
