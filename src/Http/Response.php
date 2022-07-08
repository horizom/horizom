<?php

namespace Horizom\Http;

use Horizom\Core\View;
use GuzzleHttp\Psr7\Response as BaseResponse;
use Horizom\Http\Exceptions\HttpException;
use Middlewares\Utils\Factory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use InvalidArgumentException;

final class Response extends BaseResponse
{
    /**
     * @var ResponseFactoryInterface
     * */
    protected $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * @inherit
     */
    public function __construct($status = 200, array $headers = [], $body = null, $version = '1.1', $reason = null)
    {
        parent::__construct($status, $headers, $body, $version, $reason);

        $this->streamFactory = Factory::getStreamFactory();
    }

    public static function create()
    {
        return Factory::getResponseFactory()->createResponse();
    }

    /**
     * Create new response from instance
     */
    public static function fromInstance(ResponseInterface $response): self
    {
        $status = $response->getStatusCode();
        $headers = $response->getHeaders();
        $body = $response->getBody();
        $version = $response->getProtocolVersion();
        $reason = $response->getReasonPhrase();

        return new Response($status, $headers, $body, $version, $reason);
    }

    /**
     * Redirect to specified location
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param string    $url The redirect destination.
     * @param int|null  $status The redirect HTTP status code.
     */
    public function redirect(string $url, ?int $status = null): ResponseInterface
    {
        $status = ($status === null) ? 302 : $status;
        return self::create()->withHeader('Location', $url)->withStatus($status);
    }

    /**
     * Redirect to specified location
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param string    $url The redirect destination.
     * @param int|null  $status The redirect HTTP status code.
     */
    public function redirectWithBaseUrl($url = null, int $status = null): ResponseInterface
    {
        $url = (is_null($url)) ? HORIZOM_BASE_URL : HORIZOM_BASE_URL . '/' . trim($url, '/');
        return $this->redirect($url, $status);
    }

    /**
     * Return a view as the response's content
     */
    public function view(string $name, array $data = [], $contentType = 'text/html'): ResponseInterface
    {
        $output = (string) (new View())->make($name, $data)->render();
        $body = $this->streamFactory->createStream($output);

        return self::create()->withHeader('Content-type', $contentType)->withBody($body);
    }

    /**
     * Write JSON to Response Body.
     *
     * This method prepares the response object to return an HTTP Json
     * response to the client.
     */
    public function json($data, ?int $status = null, int $options = 0, int $depth = 512): ResponseInterface
    {
        $json = (string) json_encode($data, $options, $depth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(json_last_error_msg(), json_last_error());
        }

        $body = $this->streamFactory->createStream($json);
        $response = self::create()->withHeader('Content-Type', 'application/json')->withBody($body);

        if ($status !== null) {
            $response = $response->withStatus($status);
        }

        return $response;
    }

    /**
     * This method will trigger the client to download the specified file
     * It will append the `Content-Disposition` header to the response object
     *
     * @param string|resource|StreamInterface $file
     * @param string|null $name
     * @param bool|string $contentType
     */
    public function download($file, string $name = null, $contentType = true): ResponseInterface
    {
        $disposition = 'attachment';
        $fileName = $name;

        if (is_string($file) && $name === null) {
            $fileName = basename($file);
        }

        if ($name === null && (is_resource($file) || $file instanceof StreamInterface)) {
            $metaData = $file instanceof StreamInterface
                ? $file->getMetadata()
                : stream_get_meta_data($file);

            if (is_array($metaData) && isset($metaData['uri'])) {
                $uri = $metaData['uri'];
                if ('php://' !== substr($uri, 0, 6)) {
                    $fileName = basename($uri);
                }
            }
        }

        if (is_string($fileName) && strlen($fileName)) {
            /*
             * The regex used below is to ensure that the $fileName contains only
             * characters ranging from ASCII 128-255 and ASCII 0-31 and 127 are replaced with an empty string
             */
            $disposition .= '; filename="' . preg_replace('/[\x00-\x1F\x7F\"]/', ' ', $fileName) . '"';
            $disposition .= "; filename*=UTF-8''" . rawurlencode($fileName);
        }

        $response = clone $this;
        $response->file($file, $contentType)->withHeader('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Display a file, such as an image or PDF, directly in the user's browser instead of initiating a download.
     *
     * @param string|resource|StreamInterface $file
     * @param bool|string $contentType
     *
     * @throws RuntimeException If the file cannot be opened.
     * @throws InvalidArgumentException If the mode is invalid.
     */
    public function file($file, $contentType = true): ResponseInterface
    {
        $response = self::create();

        if (is_resource($file)) {
            $response = $response->withBody($this->streamFactory->createStreamFromResource($file));
        } elseif (is_string($file)) {
            $response = $response->withBody($this->streamFactory->createStreamFromFile($file));
        } elseif ($file instanceof StreamInterface) {
            $response = $response->withBody($file);
        } else {
            throw new InvalidArgumentException(
                'Parameter 1 of Response::withFile() must be a resource, a string ' .
                    'or an instance of Psr\Http\Message\StreamInterface.'
            );
        }

        if ($contentType === true) {
            $contentType = is_string($file) ? mime_content_type($file) : 'application/octet-stream';
        }

        if (is_string($contentType)) {
            $response = $response->withHeader('Content-Type', $contentType);
        }

        return $response;
    }

    /**
     * Convert response to string.
     */
    public function __toString(): string
    {
        $response = $this;

        $output = sprintf(
            'HTTP/%s %s %s%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            "\r\n"
        );

        foreach ($response->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $response->getHeaderLine($name)) . "\r\n";
        }

        $output .= "\r\n";
        $output .= (string) $response->getBody();

        return $output;
    }
}
