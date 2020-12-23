<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace Horizom\Routing\Middleware;

class DispatcherFactory implements DispatcherFactoryInterface
{
    /**
     * @var MiddlewareResolverInterface
     */
    private $resolver;

    public function __construct(?MiddlewareResolverInterface $resolver = null)
    {
        $this->resolver = $resolver;
    }

    public function create(array $middlewares): Dispatcher
    {
        return new Dispatcher($middlewares, $this->resolver);
    }
}
