<?php

/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace Horizom\Routing;

use Closure;
use Horizom\Dispatcher\MiddlewarePipe;
use Horizom\Routing\Exception\RoutingException;
use Psr\Http\Server\MiddlewareInterface;

class Route implements RouteInterface
{
    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string[]
     */
    private $methods;

    /**
     * @var string
     */
    private $path;

    /**
     * @var Closure
     */
    private $handler;

    /**
     * @var MiddlewareInterface[]|string[]
     */
    private $middlewares = [];

    /**
     * @var array<string, mixed>
     */
    private $attributes = [];

    /**
     * @var MiddlewarePipe
     */
    private $pipe = null;

    /**
     * Route can't be modified after route is compiled
     *
     * @var bool
     */
    private  $isCompiled = false;

    /**
     * @param string[] $methods
     * @param string $path
     * @param Closure $handler
     */
    public function __construct(array $methods, string $path, Closure $handler)
    {
        $this->methods = $methods;
        $this->path = $path;
        $this->handler = $handler;
    }

    private function checkIsCompiled(): void
    {
        if ($this->isCompiled) {
            throw new \BadMethodCallException('Route is compiled');
        }
    }

    public function isCompiled(): bool
    {
        return $this->isCompiled;
    }

    /**
     * @inheritDoc
     */
    public function compile(array $args): void
    {
        if ($this->isCompiled) {
            return;
        }

        $this->isCompiled = true;

        $handler = $args['handler'] ?? null;
        // support of lazy handler resolution
        if ($handler instanceof Closure) {
            $this->handler = $handler;
        }

        $pipe = $args['pipe'] ?? null;
        if ($pipe instanceof MiddlewarePipe) {
            $this->pipe = $pipe;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getHandler(): Closure
    {
        return $this->handler;
    }

    public function getPipe(): MiddlewarePipe
    {
        if ($this->pipe === null) {
            throw new RoutingException('Route doesn\'t have pipe');
        }

        return $this->pipe;
    }

    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set route name
     */
    public function setName(string $name): self
    {
        $this->checkIsCompiled();

        $this->name = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMiddleware($middleware): self
    {
        $this->checkIsCompiled();

        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Set route name
     */
    public function name(string $name): self
    {
        return $this->setName($name);
    }

    /**
     * Add middleware to route
     *
     * @param MiddlewareInterface|string $middleware
     * @return self
     */
    public function middleware($middleware): self
    {
        return $this->withMiddleware($middleware);
    }

    /**
     * Add attribute to route
     *
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    public function attribute(string $key, $value): self
    {
        return $this->withAttribute($key, $value);
    }
}
