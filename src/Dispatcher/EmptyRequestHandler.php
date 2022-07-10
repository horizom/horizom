<?php

namespace Horizom\Dispatcher;

use Horizom\Dispatcher\Exceptions\RequestHandlerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EmptyRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new RequestHandlerException('Empty handler');
    }
}
