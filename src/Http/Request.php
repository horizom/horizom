<?php

namespace Horizom\Http;

use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\UriInterface;
use Horizom\Collection\DataCollection;
use Horizom\Collection\FilesDataCollection;
use Horizom\Collection\ServerDataCollection;

class Request extends ServerRequest
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
     * @var UriInterface 
     */
    private $uri;

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
     * The route resolver callback.
     *
     * @var \Closure
     */
    protected $routeResolver;

    public function __construct($method, $uri, array $headers = [], $body = null, $version = '1.1', array $serverParams = [])
    {
        parent::__construct($method, $uri, $headers, $body, $version, $serverParams);

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
     * Create new Request from instance
     */
    public static function fromInstance(): self
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        return new Request($method, $uri, $headers, null, $protocol, $_SERVER);
    }

    /**
     * Return the Request instance.
     */
    public function instance(): self
    {
        return $this;
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
        return $this->headers->get('User-Agent');
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
     *
     * @return bool
     */
    public function isXhr(): bool
    {
        return (strtolower($this->getHeader('http-x-requested-with')) === 'xmlhttprequest');
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
