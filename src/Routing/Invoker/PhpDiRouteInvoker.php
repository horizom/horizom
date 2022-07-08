<?php

namespace Horizom\Routing\Invoker;

use Invoker\InvokerInterface;
use Horizom\Routing\RouteInterface;
use Horizom\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PhpDiRouteInvoker implements RouteInvokerInterface
{
    /**
     * @var InvokerInterface
     */
    private $invoker;

    /**
     * @var array<class-string>
     */
    private $requestAliases;

    /**
     * PhpDiRouteInvoker constructor.
     * @param InvokerInterface $invoker
     * @param array<class-string> $requestAliases
     */
    public function __construct(InvokerInterface $invoker, array $requestAliases = [])
    {
        $this->invoker = $invoker;
        $this->requestAliases = $requestAliases;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $request->getAttribute(RouteInterface::class);
        $args = $request->getAttribute(Router::ROUTE_ARGS, []);

        $handler = $route->getHandler();

        // bind request
        $args[ServerRequestInterface::class] = $request;

        // bind request to user supplied aliases
        foreach ($this->requestAliases as $requestAlias) {
            $args[$requestAlias] = $request;
        }

        return $this->invoker->call($handler, $args);
    }
}
