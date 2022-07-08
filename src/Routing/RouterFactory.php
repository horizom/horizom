<?php

namespace Horizom\Routing;

use FastRoute\Dispatcher\GroupCountBased;

class RouterFactory implements RouterFactoryInterface
{
    public function create(RouteCollectorInterface $collector): Router
    {
        return new Router(
            new GroupCountBased($collector->getData())
        );
    }
}
