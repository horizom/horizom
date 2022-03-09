<?php

namespace Horizom\Http;

use RuntimeException;
use GuzzleHttp\Psr7\ServerRequest as BaseRequest;
use Horizom\Http\Collection\FileCollection;
use Horizom\Http\Collection\ServerCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Psr\Http\Message\UriInterface;

final class Request extends BaseRequest
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
        $base_path = config('app.base_path');
        $path = trim($base_path, '/');
        $host = $uri->getHost();
        $port = $uri->getPort();
        $uri_query = $uri->getQuery();

        if ($port && !in_array($port, [80, 443])) {
            $host = $host . ':' . $port;
        }

        $base_uri = ($path) ? $host . '/' . $path : $host;
        $request_uri = $uri->getPath();
        $queries = $this->parseQuery($uri_query);

        $this->base_path = $base_path;
        $this->request_path = str_replace($base_path, '', $uri->getPath());
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
    public static function create(): self
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = self::getUriFromGlobals();
        $headers = getallheaders();
        $body = fopen('php://input', 'r') ?: null;
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
     * @throws \RuntimeException
     */
    public function fingerprint()
    {
        if (!$route = $this->route()) {
            throw new RuntimeException('Unable to generate fingerprint. Route unavailable.');
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
