<?php

namespace App\Middlewares;

use Throwable;
use Psr\Http\Message\ResponseInterface;
use Horizom\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Horizom\Interfaces\ErrorHandlerInterface;
use Horizom\Exception\MethodNotAllowedException;

class ErrorHandlerMiddleware implements ErrorHandlerInterface
{
    public function handle(Throwable $e, ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        {
            if ($e instanceof NotFoundException) {
                return response(404)->view('errors.error_404', ['message' => $e->getMessage()]);
            }
    
            if ($e instanceof MethodNotAllowedException) {
                return response(405, ['Allow' => $e->getAllowedMethods()])
                    ->view('errors.error_405', ['message' => $e->getMessage()]);
            }
    
            return response(500)->view('errors.error_500', [
                'message' => "Internal Server Error"
            ]);
        }
    }
}
