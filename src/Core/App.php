<?php

namespace Horizom\Core;

use DI\ContainerBuilder;
use Horizom\Core\Middlewares\ErrorHandlingMiddleware;
use Horizom\Core\ServiceProvider;
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
    /**
     * @var self
     */
    private static $instance;

    /**
     * @const string Horizom Framework Version
     */
    protected const VERSION = '4.0.0';

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

    private $providers = [];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RouteCollector
     */
    public $router;

    /**
     * Create new application
     */
    public function __construct(string $basePath = '', ContainerInterface $container = null)
    {
        define("HORIZOM_VERSION", self::VERSION);

        $this->basePath = $basePath;

        if ($container === null) {
            $containerBuilder = new ContainerBuilder(Container::class);
            $containerBuilder->useAutowiring(true);

            $this->container = $containerBuilder->build();
            $this->set("version", $this->version());
        }

        $this->dispatcher = new Dispatcher([], new MiddlewareResolver($this->container));
        $this->router = (new RouteCollectorFactory)->create($this->container);

        $this->singleton(RouteCollector::class, fn() => $this->router);
        $this->registerBaseServiceProviders();

        self::$instance = $this;
    }

    /**
     * Retourne l'instance de la class
     *
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
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
     * Get the container instance.
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Build an entry of the container by its name.
     * This method behave like get() except resolves the entry again every time.
     */
    public function make(string $name, array $parameters = [])
    {
        return $this->container->make($name, $parameters);
    }

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param callable $concrete
     * @return mixed
     */
    public function singleton(string $abstract, $concrete = null)
    {
        $this->set($abstract, $concrete ?? $abstract);
    }

    /**
     * Define an object or a value in the container.
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return mixed
     */
    public function instance(string $abstract, $instance)
    {
        return $this->set($abstract, $instance);
    }

    /**
     * Define an object in the container.
     *
     * @param string $abstract
     * @param mixed $concrete
     */
    public function bind(string $abstract, $concrete)
    {
        $this->set($abstract, $concrete);
    }

    /**
     * Define an object or a value in the container.
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        return $this->container->set($name, $value);
    }

    /**
     * Get an entry of the container by its name.
     */
    public function get(string $name)
    {
        return $this->container->get($name);
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return ServiceProvider
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * Register a service provider.
     *
     * @param ServiceProvider|string $provider
     */
    public function register($provider)
    {
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        return $this->container->register($provider);
    }

    /**
     * Boots up the application calling the `boot` method of each registered service provider.
     *
     * @see \Horizom\Core\ServiceProvider::boot()
     */
    public function boot()
    {
        $this->container->boot();
    }

    /**
     * Set Configuration Values into the application.
     */
    public function setConfig(array $config)
    {
        if ($this->config === null) {
            $this->config = new Config($config);
        } else {
            $this->config->set($config);
        }

        $this->instance(Config::class, $this->config);
    }

    /**
     * Get Configuration Values from the application.
     */
    public function getConfig(string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config->get($key);
    }

    /**
     * Load a configuration file into the application.
     */
    public function configure(string $name): self
    {
        $this->setConfig(require base_path("config/{$name}.php"));
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
     * Run The Application
     */
    public function run(Request $request)
    {
        $this->singleton(Request::class, fn() => $request);

        $accepts = $request->getHeader('Accept');

        $this->registerErrorHandler($accepts);
        $this->registerServiceProvidersAndBoot();

        $this->singleton(RouteCollector::class, fn() => $this->router);
        $this->add($this->router->getRouter());

        $response = $this->dispatcher->dispatch($request);

        $this->emit($response);
    }

    /**
     * Register basic middlewares
     *
     * @param array<int, string> $accepts
     */
    protected function registerErrorHandler(array $accepts)
    {
        if (config('app.pretty_debug') === true) {
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
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        foreach ($this->providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Register service providers and boot them if the application is already booted.
     *
     * @return void
     */
    protected function registerServiceProvidersAndBoot()
    {
        $providers = (array) config('providers');

        foreach ($providers as $provider) {
            $this->register($provider);
        }

        $this->boot();
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
