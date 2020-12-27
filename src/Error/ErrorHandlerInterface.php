<?php

namespace Horizom\Error;

use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ErrorHandlerInterface
{
    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface;
}
