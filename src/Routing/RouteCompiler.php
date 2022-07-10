<?php

namespace Horizom\Routing;

use Horizom\Routing\Invoker\RouteInvokerInterface;
use Horizom\Dispatcher\MiddlewarePipeFactoryInterface;

class RouteCompiler implements RouteCompilerInterface
{
    /**
     * @var MiddlewarePipeFactoryInterface
     */
    private $pipeFactory;

    /**
     * @var RouteInvokerInterface
     */
    private $routeInvoker;

    public function __construct(MiddlewarePipeFactoryInterface $pipeFactory, RouteInvokerInterface $routeInvoker)
    {
        $this->pipeFactory = $pipeFactory;
        $this->routeInvoker = $routeInvoker;
    }

    public function compile(RouteInterface $route): void
    {
        // do not compile already compiled routes
        if ($route->isCompiled()) {
            return;
        }

        $handler = $route->getHandler();
        $handlerReflection = new \ReflectionFunction($handler);
        $promisedHandler = null;

        // route handler resolution is promised, lets resolve this promise now
        if ($handlerReflection->getClosureThis() instanceof HandlerResolver\RouteHandlerPromise) {
            // re-throw route resolution exception with debug information
            try {
                $promisedHandler = $handler();
            } catch (Exception\WrongRouteHandlerException $e) {
                throw new Exception\WrongRouteHandlerException(
                    \sprintf("%s %s: %s", \implode('|', $route->getMethods()), $route->getPath(), $e->getMessage()),
                    $e->getHandler(),
                    $e
                );
            }
        }

        $middlewares = $route->getMiddlewares();
        $middlewares[] = $this->routeInvoker;

        $pipe = $this->pipeFactory->create($middlewares);

        $route->compile(['pipe' => $pipe, 'handler' => $promisedHandler]);
    }
}
