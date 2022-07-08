<?php

namespace Horizom\Routing;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router implements RouterInterface
{
    public const ROUTE_ARGS = 'args';

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::FOUND:
                [, $route, $routeArgs] = $routeInfo;

                $request = $request
                    ->withAttribute(RouteInterface::class, $route)
                    ->withAttribute(self::ROUTE_ARGS, $routeArgs);

                return $route->getPipe()->handle($request);
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                throw new \Horizom\Routing\Exception\MethodNotAllowedException($routeInfo[1]);
            case \FastRoute\Dispatcher::NOT_FOUND:
                throw new \Horizom\Routing\Exception\NotFoundException();
            default:
                throw new \Horizom\Routing\Exception\NotFoundException();
        }
    }
}
