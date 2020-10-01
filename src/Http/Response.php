<?php

namespace Horizom\Http;

use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Horizom\Core\Renderer;

class Response extends GuzzleHttpResponse implements ResponseInterface
{
    /**
     * Application base url
     * @var string
     */
    private $baseUrl;

    /**
     * @var ResponseFactoryInterface
     * */
    private $factory;

    /**
     * @var string
     * */
    private $notFoundMessage;

    /**
     * Horizom\Http\Response
     */
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        parent::__construct($status, $headers, $body, $version, $reason);

        $this->factory = Factory::getResponseFactory();
        $this->notFoundMessage = 'The page you are looking for could not be found.';
    }

    /**
     * Redirect the user to another URL
     */
    public function redirect($url = null, bool $external = false, int $code = 302): ResponseInterface
    {
        if (!$external) {
            $url = (is_null($url)) ? $this->baseUrl : $this->baseUrl . '/' . trim($url, '/');
        }

        return $this->factory->createResponse($code)->withHeader('Location', $url);
    }

    /**
     * Return a view as the response's content
     * @TODO: implement blade tamplate
     */
    public function view(string $name, array $data = []): ResponseInterface
    {
        $output = Renderer::make($name, $data);
        $response = $this->withHeader('Content-type', 'text/html');
        $response->getBody()->write($output);

        return $response;
    }

    /**
     * Automatically set the Content-Type header to application/json, as well as convert the given array to JSON
     */
    public function json(array $data, int $statusCode = 200): ResponseInterface
    {
        $response = $this->withHeader('Content-type', 'application/json')->withStatus($statusCode);
        $response->getBody()->write(json_encode($data));

        return $response;
    }

    /**
     * NotFound response
     * @param string|null
     */
    public function notFound($message = null)
    {
        $output = $message ? $message : $this->notFoundMessage;
        $response = $this->factory->createResponse(404);
        $response->getBody()->write($output);

        return $response;
    }

    /**
     * Not allowed response
     */
    public function notAllowed(array $methods)
    {
        $header = implode(', ', $methods);
        $output = "Method not allowed. Must be one of: $header.";

        $response = $this->factory->createResponse(405)->withHeader('Allow', $header);
        $response->getBody()->write($output);

        return $response;
    }

    /**
     * Redirect the user to their previous location, such as when a submitted form is invalid.
     */
    public function back()
    {
        # code...
    }

    /**
     * Generate a response that forces the user's browser to download the file at the given path.
     */
    public function download(string $filepath, string $name, string $headers)
    {
        # code...
    }

    /**
     * Display a file, such as an image or PDF, directly in the user's browser instead of initiating a download.
     */
    public function file(string $filepath, array $headers)
    {
        # code...
    }
}
