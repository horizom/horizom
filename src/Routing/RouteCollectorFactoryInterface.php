<?php

namespace Horizom\Routing;

use Psr\Container\ContainerInterface;

interface RouteCollectorFactoryInterface
{
    public function create(ContainerInterface $container): RouteCollectorInterface;
}
