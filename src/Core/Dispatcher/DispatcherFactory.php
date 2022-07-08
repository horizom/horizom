<?php

namespace Horizom\Core\Dispatcher;

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
