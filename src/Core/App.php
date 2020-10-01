<?php

namespace Horizom\Core;

use Horizom\Http\Route;
use Horizom\Router\Router;
use Horizom\Database\Manager as DatabaseManager;
use GuzzleHttp\Psr7\ServerRequest;
use Middlewares\Utils\Factory;
use Middlewares\Utils\FactoryDiscovery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class App
{
    /**
     * @const string Horizom Framework Version
     */
    private const VERSION = '1.1.0';

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * All of the configuration items.
     *
     * @var array
     */
    private static $settings = [];

    /**
     * Guzzle factory strategies
     */
    private const GUZZLE_FACTORY = [
        'request' => 'Http\Factory\Guzzle\RequestFactory',
        'response' => 'Http\Factory\Guzzle\ResponseFactory',
        'serverRequest' => 'Http\Factory\Guzzle\ServerRequestFactory',
        'stream' => 'Http\Factory\Guzzle\StreamFactory',
        'uploadedFile' => 'Http\Factory\Guzzle\UploadedFileFactory',
        'uri' => 'Http\Factory\Guzzle\UriFactory',
    ];

    public function __construct(array $settings)
    {
        define("HORIZOM_VERSION", self::VERSION);
        
        self::$settings = $settings;

        if (config('database.connections') && config('database.default')) {
            $connections = config('database.connections');
            $defaultConnection = config('database.default');

            new DatabaseManager($connections, $defaultConnection);
        }

        Renderer::init();
        Route::map(Router::init());
        Factory::setFactory(new FactoryDiscovery(self::GUZZLE_FACTORY));

        // Include routes configurations
        require HORIZOM_CONFIG . 'routes.php';
    }

    /**
     * Get Configuration Values
     */
    public static function config()
    {
        return self::$settings;
    }

    /**
     * Register a new middleware
     *
     * @param callable|MiddlewareInterface $middleware
     * @return void
     */
    public function add($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Launch the application
     */
    public function run()
    {
        $dispatcher = new Dispatcher();

        foreach ($this->middlewares as $middlware) {
            $dispatcher->pipe($middlware);
        }

        // Cors middleware
        $dispatcher->pipe(new \Horizom\Middlewares\Cors());

        // Whoops middleware
        $dispatcher->pipe(new \Middlewares\Whoops());

        // Router middleware
        $dispatcher->pipe(new \Horizom\Middlewares\Router());

        $request = ServerRequest::fromGlobals();
        $response = $dispatcher->dispatch($request);

        $this->send($response);
    }

    /**
     * Send an HTTP response
     */
    private function send(ResponseInterface $response)
    {
        $http_line = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        header($http_line, true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            echo $stream->read(1024 * 8);
        }
    }
}
