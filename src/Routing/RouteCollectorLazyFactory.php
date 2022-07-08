<?php

namespace Horizom\Routing;

use Invoker\CallableResolver;
use Horizom\Routing\HandlerResolver\LazyRouteHandlerResolver;
use Horizom\Routing\HandlerResolver\PhpDiRouteHandlerResolver;
use Psr\Container\ContainerInterface;

class RouteCollectorLazyFactory extends RouteCollectorFactory
{
    public function create(ContainerInterface $container): RouteCollector
    {
        return new RouteCollector(
            new \FastRoute\RouteParser\Std(),
            new \FastRoute\DataGenerator\GroupCountBased(),
            new LazyRouteHandlerResolver(
                new PhpDiRouteHandlerResolver(
                    new CallableResolver($container)
                )
            ),
            $this->getCompiler($container),
            new RouterFactory()
        );
    }
}
