<?php

namespace Horizom\Core;

use Horizom\Core\Middlewares\ErrorHandlingMiddleware;
use Horizom\Dispatcher\Dispatcher;
use Horizom\Dispatcher\MiddlewareResolver;
use Horizom\Http\Request;
use Horizom\Routing\RouteCollector;
use Horizom\Routing\RouteCollectorFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class App
{
    use AppContainerTrait;

    /**
     * @const string Horizom Framework Version
     */
    protected const VERSION = '4.0.0';

    /**
     * @var array
     */
    protected static $settings = [
        'app.name' => 'Horizom',

        'app.env' => 'development',

        'app.base_path' => '',

        'app.base_url' => 'http://localhost:8000',

        'app.asset_url' => null,

        'app.timezone' => 'UTC',

        'app.locale' => 'en_US',

        'app.pretty_debug' => true,

        'providers' => [],

        'aliases' => [],
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
     * @var Container
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
     * The current globally available app (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * Create new application
     */
    public function __construct(string $basePath = '', ContainerInterface $container = null)
    {
        define("HORIZOM_VERSION", self::VERSION);

        $this->basePath = $basePath;
        $this->container = $container ?? new Container();

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();

        $this->dispatcher = new Dispatcher([], new MiddlewareResolver($this->container));
        $this->router = (new RouteCollectorFactory())->create($this->container);
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param Container|null  $container
     * @return Container|static
     */
    public static function setInstance(Container $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        $this->instance("version", $this->version());
        $this->instance('app', $this);
        $this->instance(Container::class, $this->container);
        $this->instance(Request::class, Request::create());
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $providers = (array) config('providers');

        foreach ($providers as $provider) {
            $this->register(new $provider($this));
        }
    }

    /**
     * Dependency Injection Container.
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Set or Get Configuration Values into the application.
     */
    public static function config(array $config = null)
    {
        if ($config !== null) {
            self::$settings = array_merge(self::$settings, $config);
            return null;
        }

        return self::$settings;
    }

    /**
     * Load a configuration file into the application.
     */
    public function configure(string $name): self
    {
        $config = require HORIZOM_ROOT . '/config/' . $name . '.php';
        $this->config($config);

        return $this;
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
     * Register a new middleware in stack
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
        $request = $this->container->get(Request::class);

        if (config('app.pretty_debug') === true) {
            $accepts = $request->getHeader('Accept');
            $whoops = new \Whoops\Run();

            if (
                !empty($accepts) && $accepts[0] === 'application/json' ||
                \Whoops\Util\Misc::isAjaxRequest()
            ) {
                $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
            } else {
                $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            }

            $this->add(new \Middlewares\Whoops($whoops));
        } elseif ($this->errorHandler !== null) {
            $this->add(new ErrorHandlingMiddleware($this->errorHandler));
        }

        $this->add($this->router->getRouter());
        $response = $this->dispatcher->dispatch($request);

        $this->emit($response);
    }

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
