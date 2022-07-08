<?php

namespace Horizom\Routing\HandlerResolver;

use Closure;
use Invoker\CallableResolver;
use Invoker\Exception\NotCallableException;
use Horizom\Routing\Exception;
use ReflectionException;

use function explode;
use function is_string;
use function strpos;

class PhpDiRouteHandlerResolver implements RouteHandlerResolverInterface
{
    use RouteHandlerResolverTrait;

    /**
     * @var CallableResolver
     */
    private $resolver;

    public function __construct(CallableResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @inheritDoc
     */
    public function resolve($callable): Closure
    {
        if (is_string($callable) && 0 !== strpos($callable, '@')) {
            $callable = explode('@', $callable, 2);
        }

        try {
            $handler = Closure::fromCallable($this->resolver->resolve($callable));
        } catch (NotCallableException | ReflectionException $e) {
            throw new Exception\WrongRouteHandlerException($e->getMessage(), $callable, $e);
        }

        $this->validateReturnType($handler, $callable);

        return $handler;
    }
}
