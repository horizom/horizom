<?php

namespace Horizom\Matchers;

use Psr\Http\Message\ServerRequestInterface;

interface MatcherInterface
{
    /**
     * Evaluate if the request matches with the condition
     */
    public function __invoke(ServerRequestInterface $request): bool;
}
