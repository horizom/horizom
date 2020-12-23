<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace Horizom\Routing;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;

use function class_exists;
use function is_string;
use function mb_strpos;
use function rtrim;

class RouteCollector implements RouteCollectorInterface
{
    /**
     * @var RouteInterface[]
     */
    protected $routes = [];

    /**
     * @var string
     */
    protected $currentGroupPrefix = '';

    /**
     * @var array<string,mixed>
     */
    protected $currentGroupParameters = [];

    /**
     * @var HandlerResolver\RouteHandlerResolverInterface
     */
    protected $handlerResolver;

    /**
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var RouteCompilerInterface
     */
    protected $routeCompiler;

    /**
     * @var RouterFactoryInterface
     */
    protected $routerFactory;

    public function __construct(
        RouteParser $routeParser,
        DataGenerator $dataGenerator,
        HandlerResolver\RouteHandlerResolverInterface $handlerResolver,
        RouteCompilerInterface $routeCompiler,
        RouterFactoryInterface $routerFactory
    ) {
        $this->handlerResolver = $handlerResolver;
        $this->routeParser = $routeParser;
        $this->dataGenerator = $dataGenerator;
        $this->routeCompiler = $routeCompiler;
        $this->routerFactory = $routerFactory;
    }

    /**
     * Add a route that handles multiple HTTP request methods
     * 
     * @param string|string[] $httpMethod
     * @param string $path
     * @param callable|\Closure|string|string[] $handler
     */
    public function map($httpMethod, string $path, $handler): RouteInterface
    {
        return $this->addRoute($httpMethod, $path, $handler);
    }

    /**
     * Create a route group with a common prefix.
     */
    public function group(array $parameters, callable $callback)
    {
        $prefix = '';

        if (isset($parameters['prefix'])) {
            $prefix = $parameters['prefix'];
            unset($parameters['prefix']);
        }

        $this->addGroup($prefix, $parameters, $callback);
    }

    /**
     * @inheritDoc
     *
     * @return Route
     */
    public function get(string $path, $handler): RouteInterface
    {
        return $this->addRoute([RouteInterface::METHOD_GET], $path, $handler);
    }

    /**
     * @inheritDoc
     *
     * @return Route
     */
    public function head(string $path, $handler): RouteInterface
    {
        return $this->addRoute([RouteInterface::METHOD_HEAD], $path, $handler);
    }

    /**
     * @inheritDoc
     *
     * @return Route
     */
    public function post(string $path, $handler): RouteInterface
    {
        return $this->addRoute([RouteInterface::METHOD_POST], $path, $handler);
    }

    /**
     * @inheritDoc
     *
     * @return Route
     */
    public function put(string $path, $handler): RouteInterface
    {
        return $this->addRoute([RouteInterface::METHOD_PUT], $path, $handler);
    }

    /**
     * @inheritDoc
     *
     * @return Route
     */
    public function delete(string $path, $handler): RouteInterface
    {
        return $this->addRoute([RouteInterface::METHOD_DELETE], $path, $handler);
    }

    /**
     * @inheritDoc
     *
     * @return Route
     */
    public function options(string $path, $handler): RouteInterface
    {
        return $this->addRoute([RouteInterface::METHOD_OPTIONS], $path, $handler);
    }

    /**
     * @inheritDoc
     *
     * @return Route
     */
    public function patch(string $path, $handler): RouteInterface
    {
        return $this->addRoute([RouteInterface::METHOD_PATCH], $path, $handler);
    }

    /**
     * @inheritDoc
     */
    public function addRoute($httpMethod, string $path, $handler): RouteInterface
    {
        $httpMethod = (array)$httpMethod;
        $path = $this->normalizedPath($path);

        $path = $this->currentGroupPrefix . $path;
        if ('' === $path) {
            $path = '/';
        }

        // Parse route URL
        $routeDatum = $this->routeParser->parse($path);

        // Create route handler
        if (is_string($handler) && !class_exists($handler)) {
            $handler = ($this->currentGroupParameters['namespace'] ?? '') . $handler;
        }

        $handler = $this->handlerResolver->resolve($handler);

        $route = new Route(
            $httpMethod,
            $path,
            $handler,
        );
        $this->addGroupParametersToRoute($route);

        foreach ($httpMethod as $method) {
            foreach ($routeDatum as $routeData) {
                $this->dataGenerator->addRoute(
                    $method,
                    $routeData,
                    $route
                );
            }
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * @inheritDoc
     */
    public function addGroup(string $prefix, array $parameters, callable $callback): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupParameters = $this->currentGroupParameters;

        $prefix = $this->normalizedPath($prefix);

        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $this->currentGroupParameters = $this->mergeRecursive(false, $previousGroupParameters, $parameters);

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupParameters = $previousGroupParameters;
    }

    public function getData(): array
    {
        return $this->dataGenerator->getData();
    }

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRouter(): RouterInterface
    {
        foreach ($this->routes as $route) {
            $this->routeCompiler->compile($route);
        }

        return $this->routerFactory->create($this);
    }

    protected function normalizedPath(string $path): string
    {
        // add slash to the begin of prefix
        if (0 !== mb_strpos($path, '/')) {
            $path = '/' . $path;
        }

        // remove slash from the end of prefix
        return rtrim($path, '/');
    }

    protected function addGroupParametersToRoute(Route $route): void
    {
        $middlewares = (array)($this->currentGroupParameters['middleware'] ?? []);

        foreach ($middlewares as $middleware) {
            $route->withMiddleware($middleware);
        }

        $attributes = (array)($this->currentGroupParameters['attributes'] ?? []);
        foreach ($attributes as $attributeKey => $attributeValue) {
            $route->withAttribute($attributeKey, $attributeValue);
        }
    }

    /**
     * @param bool $preserveIntegerKeys
     * @param mixed[] ...$arrays
     * @return mixed[]
     */
    protected function mergeRecursive(bool $preserveIntegerKeys, array ...$arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                // Renumber integer keys as array_merge_recursive() does unless
                // $preserveIntegerKeys is set to TRUE. Note that PHP automatically
                // converts array keys that are integer strings (e.g., '1') to integers.
                if (\is_int($key) && !$preserveIntegerKeys) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && \is_array($result[$key]) && \is_array($value)) {
                    $result[$key] = $this->mergeRecursive($preserveIntegerKeys, $result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
