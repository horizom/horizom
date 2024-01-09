<?php

namespace App\Middlewares;

use Throwable;
use Horizom\Core\ErrorHandlerInterface;
use Horizom\Routing\Exception\NotFoundException;
use Horizom\Routing\Exception\MethodNotAllowedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorHandlerMiddleware implements ErrorHandlerInterface
{
    public function handle(Throwable $e, ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $code = 500;
        $title = 'Internal Error';
        $message = 'An internal error has occurred.'; // or $e->getMessage()
        $headers = [];

        if ($e instanceof NotFoundException) {
            $code = 404;
            $title = 'Not Found';
            $message = 'The requested resource was not found.';
        }

        if ($e instanceof MethodNotAllowedException) {
            $code = 405;
            $title = 'Method Not Allowed';
            $message = 'The method is not allowed for the requested URL.';
            $headers = ['Allow' => implode(', ', $e->getAllowedMethods())];
        }

        $data = [
            'code' => $code,
            'title' => $title,
            'message' => $message,
            'trace' => $e->getTraceAsString(),
        ];

        $server = collect($request->getServerParams());
        $paramKey = 'HTTP_X_REQUESTED_WITH';

        if ($server->has($paramKey) && strtolower($server->get($paramKey)) == 'xmlhttprequest') {
            $response = response($code, $headers)->json($data);
        } else {
            $response = response($code, $headers)->view('errors', $data);
        }

        return $response;
    }
}
