<?php

namespace Horizom\Routing;

use Invoker\CallableResolver;
use Invoker\Invoker;
use Invoker\ParameterResolver;
use Horizom\Dispatcher\MiddlewarePipeFactory;
use Horizom\Dispatcher\MiddlewareResolver;
use Horizom\Routing\HandlerResolver\PhpDiRouteHandlerResolver;
use Horizom\Routing\Invoker\PhpDiRouteInvoker;
use Psr\Container\ContainerInterface;

class RouteCollectorFactory implements RouteCollectorFactoryInterface
{
    /**
     * User defined request class aliases for DI
     *
     * @var array<class-string>
     */
    private $requestAliases;

    /**
     * @param array<class-string> $requestAliases
     */
    public function __construct(array $requestAliases = [])
    {
        $this->requestAliases = $requestAliases;
    }

    public function create(ContainerInterface $container): RouteCollector
    {
        return new RouteCollector(
            new \FastRoute\RouteParser\Std(),
            new \FastRoute\DataGenerator\GroupCountBased(),
            new PhpDiRouteHandlerResolver(
                new CallableResolver($container)
            ),
            $this->getCompiler($container),
            new RouterFactory()
        );
    }

    protected function getCompiler(ContainerInterface $container): RouteCompiler
    {
        return new RouteCompiler(
            new MiddlewarePipeFactory(
                new MiddlewareResolver($container)
            ),
            new PhpDiRouteInvoker(
                new Invoker(
                    new ParameterResolver\ResolverChain(
                        [
                            new ParameterResolver\TypeHintResolver(),
                            new ParameterResolver\AssociativeArrayResolver(),
                            new ParameterResolver\NumericArrayResolver(),
                            new ParameterResolver\Container\TypeHintContainerResolver($container),
                            new ParameterResolver\DefaultValueResolver(),
                        ]
                    ),
                    null, // performance optimization
                ),
                $this->requestAliases
            )
        );
    }
}
