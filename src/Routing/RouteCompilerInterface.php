<?php

namespace Horizom\Routing;

interface RouteCompilerInterface
{
    public function compile(RouteInterface $route): void;
}
