<?php

namespace Horizom\Dispatcher;

use Horizom\Dispatcher\Exceptions\RequestHandlerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Flat list middleware dispatcher implementation
 */
class Dispatcher implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]|RequestHandlerInterface[]
     */
    private $middlewares = [];

    /**
     * @var MiddlewareResolverInterface
     */
    private $resolver;

    /**
     * @var int
     */
    private $currentStep = 0;

    /**
     * @param MiddlewareInterface[]|RequestHandlerInterface[]|string[] $middlewares
     * @param MiddlewareResolverInterface|null $resolver
     */
    public function __construct(array $middlewares = [], ?MiddlewareResolverInterface $resolver = null)
    {
        $this->resolver = ($resolver !== null) ? $resolver : new MiddlewareResolver();

        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }

    /**
     * Add new middleware in stack
     * 
     * @param MiddlewareInterface|string|callable $middleware
     */
    public function add($middleware)
    {
        $this->middlewares[] = $this->resolver->resolve($middleware);
    }

    /**
     * @see RequestHandlerInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $step = $this->middlewares[$this->currentStep] ?? null;

        if ($step === null) {
            throw new RequestHandlerException("Step {$this->currentStep} not found");
        }

        $this->currentStep++;

        if ($step instanceof MiddlewareInterface) {
            return $step->process($request, $this);
        }

        if ($step instanceof RequestHandlerInterface) {
            $this->currentStep = 0;

            return $step->handle($request);
        }
    }

    /**
     * Dispatch the request, return a response.
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        reset($this->middlewares);
        return $this->handle($request);
    }

    /**
     * Magic method to execute the dispatcher as a callable
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }
}
