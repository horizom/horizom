<?php

declare(strict_types=1);

namespace Horizom\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface DispatcherFactoryInterface
{
    /**
     * @param MiddlewareInterface[]|RequestHandlerInterface[]|string[] $middlewares
     *
     * @return Dispatcher
     */
    public function create(array $middlewares): Dispatcher;
}
