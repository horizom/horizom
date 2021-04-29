<?php

namespace Horizom;

use Horizom\Dispatcher\Dispatcher;
use Horizom\Dispatcher\MiddlewareResolver;
use Horizom\Http\Request;
use Horizom\Interfaces\ErrorHandlerInterface;
use Horizom\Middleware\ErrorHandlingMiddleware;
use Horizom\Routing\RouteCollector;
use Horizom\Routing\RouteCollectorFactory;
use Middlewares\Utils\Factory;
use Middlewares\Utils\FactoryDiscovery;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class App
{
    /**
     * @const string Horizom Framework Version
     */
    protected const VERSION = '2.1.3';

    /**
     * @var array
     */
    protected static $settings = [
        'app.name' => 'Horizom',

        'app.env' => 'development',

        'app.debug' => false,

        'app.base_path' => '',

        'app.url' => 'http://localhost',

        'app.asset_url' => null,

        'app.timezone' => 'UTC',

        'app.locale' => 'en',

        'app.display_errors' => true,

        'system.redirect.https' => false,

        'system.redirect.www' => false,
    ];

    /**
     * @var string
     */
    protected $defaultNamespace;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var ErrorHandlerInterface
     */
    private $errorHandler;

    /**
     * @var RouteCollector
     */
    public $router;

    /**
     * Guzzle factory strategies
     */
    private const GUZZLE_FACTORY = [
        'request' => 'Http\Factory\Guzzle\RequestFactory',
        'response' => 'Http\Factory\Guzzle\ResponseFactory',
        'serverRequest' => 'Http\Factory\Guzzle\ServerRequestFactory',
        'stream' => 'Http\Factory\Guzzle\StreamFactory',
        'uploadedFile' => 'Http\Factory\Guzzle\UploadedFileFactory',
        'uri' => 'Http\Factory\Guzzle\UriFactory'
    ];

    /**
     * Create new application
     */
    public function __construct(string $basePath = '', ContainerInterface $container = null)
    {
        define("HORIZOM_VERSION", self::VERSION);

        $this->basePath = $basePath;
        Factory::setFactory(new FactoryDiscovery(self::GUZZLE_FACTORY));

        if ($container === null) {
            $container = new Container();
            $container->set("version", $this->version());
            $container->set(\Horizom\Http\Request::class, Request::create());
        }

        $resolver = new MiddlewareResolver($container);

        $this->dispatcher = new Dispatcher([], $resolver);
        $this->router = (new RouteCollectorFactory())->create($container);
        $this->container = $container;
    }

    /**
     * Get Configuration Values
     */
    public static function config()
    {
        return self::$settings;
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return 'Horizom (' . self::VERSION . ') PHP (' . PHP_VERSION . ')';
    }

    /**
     * Load a configuration file into the application.
     */
    public function configure(string $name): self
    {
        $config = require HORIZOM_ROOT . '/config/' . $name . '.php';
        self::$settings = array_merge(self::$settings, $config);

        return $this;
    }

    /**
     * @return \DI\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set your application base path
     * 
     * If you want to run your Slim Application from a sub-directory 
     * in your Server’s Root instead of creating a Virtual Host
     * 
     * @param string $path Path to your Application
     */
    public function setBasePath(string $path = ''): self
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Set error handler middleware
     * 
     * @param ErrorHandlerInterface|string $errorHandler
     */
    public function setErrorHandler($errorHandler): self
    {
        if (is_string($errorHandler)) {
            $this->errorHandler = new $errorHandler();
        } else {
            $this->errorHandler = $errorHandler;
        }

        return $this;
    }

    /**
     * Register a new middleware
     * 
     * @param MiddlewareInterface|string|callable $middleware
     * @return self
     */
    public function add($middleware): self
    {
        $this->dispatcher->add($middleware);
        return $this;
    }

    /**
     * Run The Application
     */
    public function run()
    {
        $request = $this->container->get(\Horizon\Http\Request::class);

        if (config('app.display_errors') === true) {
            $this->add(new \Middlewares\Whoops());
        }

        if (config('app.debug') === false) {
            if ($this->errorHandler !== null) {
                $this->add(new ErrorHandlingMiddleware($this->errorHandler));
            }
        }

        $this->dispatcher->add($this->router->getRouter());
        $response = $this->dispatcher->dispatch($request);

        $this->emit($response);
    }

    /**
     * Convert response to string.
     */
    private function emit(ResponseInterface $response)
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
