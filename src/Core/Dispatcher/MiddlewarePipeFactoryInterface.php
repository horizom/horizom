<?php

namespace Horizom\Core\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewarePipeFactoryInterface
{
    /**
     * @param MiddlewareInterface[]|RequestHandlerInterface[]|string[] $middlewares
     *
     * @return MiddlewarePipe
     *
     * @throws \InvalidArgumentException when pipeline is empty
     */
    public function create(array $middlewares): MiddlewarePipe;
}
