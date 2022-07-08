<?php

namespace Horizom\Routing;

interface RouterFactoryInterface
{
    public function create(RouteCollectorInterface $collector): RouterInterface;
}
