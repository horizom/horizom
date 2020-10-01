<?php

namespace Horizom\Core;

use Closure;
use LogicException;
use InvalidArgumentException;
use Middlewares\Utils\Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher implements RequestHandlerInterface
{
    /**
     * Middlewares stack
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @var RequestHandlerInterface|null
     */
    private $next;

    /**
     * Magic method to execute the dispatcher as a callable
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }

    /**
     * Add new middleware to queue
     */
    public function pipe(MiddlewareInterface $middlware)
    {
        $this->middlewares[] = $middlware;
    }

    /**
     * Dispatch the request, return a response.
     */
    public function dispatch(RequestInterface $request): ResponseInterface
    {
        reset($this->middlewares);
        return $this->handle($request);
    }

    /**
     * Handles the current entry in the middleware queue and advances.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->get($request);

        if ($middleware === false) {
            if ($this->next !== null) {
                return $this->next->handle($request);
            } else {
                return Factory::createResponse();
            }

            throw new LogicException('Middleware queue exhausted');
        }

        return $middleware->process($request, $this);
    }

    /**
     * @see MiddlewareInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->next = $next;
        return $this->dispatch($request);
    }

    /**
     * Return the next available middleware in the stack.
     *
     * @return MiddlewareInterface|false
     */
    private function get(ServerRequestInterface $request)
    {
        $middleware = current($this->middlewares);
        next($this->middlewares);

        if ($middleware === false) {
            return $middleware;
        }

        if (is_array($middleware)) {
            $conditions = $middleware;
            $middleware = array_pop($conditions);

            foreach ($conditions as $condition) {
                if ($condition === true) {
                    continue;
                }

                if ($condition === false) {
                    return $this->get($request);
                }

                if (!is_callable($condition)) {
                    throw new InvalidArgumentException('Invalid matcher. Must be a boolean, string or a callable');
                }

                if (!$condition($request)) {
                    return $this->get($request);
                }
            }
        }

        if ($middleware instanceof Closure) {
            return self::createMiddlewareFromClosure($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new InvalidArgumentException(sprintf('No valid middleware provided (%s)', is_object($middleware) ? get_class($middleware) : gettype($middleware)));
    }

    /**
     * Create a middleware from a closure
     */
    private function createMiddlewareFromClosure(\Closure $handler): MiddlewareInterface
    {
        return new class ($handler) implements MiddlewareInterface
        {
            private $handler;
            public function __construct(\Closure $handler)
            {
                $this->handler = $handler;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
            {
                return call_user_func($this->handler, $request, $next);
            }
        };
    }
}
