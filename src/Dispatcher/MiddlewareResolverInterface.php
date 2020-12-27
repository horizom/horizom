<?php

declare(strict_types=1);

namespace Horizom\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareResolverInterface
{
    /**
     * @param string|MiddlewareInterface|RequestHandlerInterface $middleware
     *
     * @return MiddlewareInterface|RequestHandlerInterface
     */
    public function resolve($middleware);
}
