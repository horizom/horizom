<?php

namespace Horizom\Routing\HandlerResolver;

use Closure;
use InvalidArgumentException;

class LazyRouteHandlerResolver implements RouteHandlerResolverInterface
{
    /**
     * @var RouteHandlerResolverInterface
     */
    private $resolver;

    public function __construct(RouteHandlerResolverInterface $resolver)
    {
        if ($resolver instanceof self) {
            throw new InvalidArgumentException('Cannot use self as route resolver');
        }

        $this->resolver = $resolver;
    }

    public function resolve($callable): Closure
    {
        $promise = new RouteHandlerPromise($callable, Closure::fromCallable([$this->resolver, 'resolve']));

        return Closure::fromCallable($promise);
    }
}
