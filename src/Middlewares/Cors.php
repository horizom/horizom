<?php

namespace Horizom\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Cors implements MiddlewareInterface
{
    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $maxAge = ' 3600';
        $origin = ' *';
        $methods = 'GET, POST, PUT, DELETE, PATCH, OPTIONS';
        $headers = 'Content-Type, Accept, Access-Control-Allow-Headers, Authorization, X-Requested-With';

        return $response
            ->withHeader('Access-Control-Max-Age', $maxAge)
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', $methods)
            ->withHeader('Access-Control-Allow-Headers', $headers);
    }
}