<?php

namespace Horizom\Http;

use Horizom\Http\Exceptions\HttpException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Request extends \Nyholm\Psr7\ServerRequest
{
    use RequestInputTrait;

    /**
     * @var string
     */
    private $base_path;

    /**
     * @var string
     */
    private $request_path;

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
        $query = $uri->getQuery();

        if ($port && !in_array($port, [80, 443])) {
            $host = $host . ':' . $port;
        }

        $base_uri = ($path) ? $host . '/' . $path : $host;
        $request_uri = $uri->getPath();

        $this->base_path = $basePath;
        $this->request_path = str_replace($basePath, '', $uri->getPath());
        $this->base_url = $uri->getScheme() . '://' . $base_uri;
        $this->full_url = $this->url = $uri->getScheme() . '://' . $host . $request_uri;

        if ($query) {
            $this->full_url = $this->full_url . '?' . $query;
        }

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
     * Verify that the HTTP verb matches a given string
     */
    public function isMethod(string $method)
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Retrieve a message header value by the given case-insensitive name.
     */
    public function header(string $key, $default = null)
    {
        $entries = $this->getHeader($key);
        return !empty($entries) ? $entries[0] : $default;
    }

    /**
     * Retrieve all headers from the request
     */
    public function headers()
    {
        return $this->getHeaders();
    }

    /**
     * Retrieve a bearer token from the Authorization header
     */
    public function bearerToken()
    {
        $header = $this->getHeader('Authorization');

        if (!$header[0] || strpos($header[0], 'Bearer ') !== 0) {
            return null;
        }

        return trim(substr($header[0], 7));
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
     * @throws HttpException
     */
    public function fingerprint()
    {
        if (!$this->route()) {
            throw new HttpException('Unable to generate fingerprint. Route unavailable.');
        }

        return sha1(implode('|', [
            $this->getMethod(),
            $this->root(),
            $this->path(),
            $this->ip(),
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
        return count($this->getHeader('X-PJAX')) > 0;
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
}