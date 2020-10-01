<?php

namespace Horizom\Middlewares;

use Aura\Router\Route;
use Aura\Router\RouterContainer;
use Horizom\Http\Request;
use Horizom\Http\Response;
use Horizom\Router\Router as BaseRouter;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements MiddlewareInterface
{
    /**
     * @var RouterContainer The router container
     */
    private $router;

    /**
     * @var string Attribute name for handler reference
     */
    private $attribute = 'request-handler';

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * Set the AuraRouterContainer instance.
     */
    public function __construct(ResponseFactoryInterface $responseFactory = null)
    {
        $this->router = BaseRouter::container();
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
    }

    /**
     * Set the attribute name to store handler reference.
     */
    public function attribute(string $attribute): self
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $baseResponse = $handler->handle($request);
        $matcher = $this->router->getMatcher();
        $route = $matcher->match($request);
        $response = $this->horizomResponseBridge($baseResponse);

        if (!$route) {
            $failedRoute = $matcher->getFailedRoute();

            switch ($failedRoute->failedRule) {
                case 'Aura\Router\Rule\Allows':
                    return $response->notAllowed($failedRoute->allows); // 405 METHOD NOT ALLOWED
                case 'Aura\Router\Rule\Accepts':
                    return $this->responseFactory->createResponse(406); // 406 NOT ACCEPTABLE
                case 'Aura\Router\Rule\Host':
                case 'Aura\Router\Rule\Path':
                    return $response->notFound(); // 404 NOT FOUND
            }

            return $this->responseFactory->createResponse(500); // 500 INTERNAL SERVER ERROR
        } else {
            $request = $this->horizomRequestBridge($request, $route);

            $routeData = $request->route();
            $controllerName = $routeData['controller'];
            $action = $routeData['action'];

            if (!file_exists(HORIZOM_APP . 'Controller/' . $controllerName . '.php')) {
                return $response->notFound();
            }

            $controllerClass = HORIZOM_APP_NAMESPACE . '\\Controller\\' . str_replace('/', '\\', $controllerName);
            $controller = new $controllerClass();

            if (!method_exists($controller, $action)) {
                return $response->notFound();
            }

            $attributes = $request->getAttributes();
            return $response = call_user_func_array([$controller, $action], [$request, $response, $attributes]);
        }
    }

    /**
     * PSR-7 request bridge
     */
    private function horizomRequestBridge(ServerRequestInterface $request, Route $route): Request
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        $headers = $request->getHeaders();
        $body = $request->getBody();
        $version = $request->getProtocolVersion();

        $horizomRequest = new Request($method, $uri, $headers, $body, $version);
        $request = $horizomRequest->parseRoute($route);

        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $request = $request->withAttribute($this->attribute, $route->handler);

        return $request;
    }

    /**
     * PSR-7 response bridge
     */
    private function horizomResponseBridge(ResponseInterface $baseResponse): Response
    {
        $status = $baseResponse->getStatusCode();
        $headers = $baseResponse->getHeaders();
        $body = $baseResponse->getBody();
        $version = $baseResponse->getProtocolVersion();
        $reason = $baseResponse->getReasonPhrase();

        return new Response($status, $headers, $body, $version, $reason);
    }
}
