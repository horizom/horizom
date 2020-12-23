<?php

namespace Horizom;

use App\Middlewares\ErrorHandlerMiddleware;
use Horizom\Http\Request;
use Horizom\Http\Response;
use Horizom\Routing\RouteCollector;
use Horizom\Routing\RouteCollectorFactory;
use Horizom\Routing\Middleware\ErrorHandlingMiddleware;
use Middlewares\Utils\Factory;
use Middlewares\Utils\FactoryDiscovery;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Illuminate\Database\Capsule\Manager as DatabaseManager;

class App
{
    /**
     * @const string Horizom Framework Version
     */
    protected const VERSION = '2.0.0';

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

        'system.redirect.https' => false,

        'system.redirect.www' => false,
    ];

    /**
     * @var string
     */
    protected $defaultNamespace;

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

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var RouteCollector
     */
    public $router;

    /**
     * Create new application
     */
    public function __construct(ContainerInterface $container = null, string $basePath = '')
    {
        define("HORIZOM_VERSION", self::VERSION);
        
        $this->basePath = $basePath;
        $this->dispatcher = new Dispatcher();

        if ($container === null) {
            $containerBuilder = new \DI\ContainerBuilder();
            $this->container = $containerBuilder->build();
        }

        $this->router = (new RouteCollectorFactory())->create($this->container);
        Factory::setFactory(new FactoryDiscovery(self::GUZZLE_FACTORY));
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return 'Horizom ('.self::VERSION.') PHP (' . PHP_VERSION .')';
    }

    /**
     * Load a configuration file into the application.
     */
    public function configure(string $name): self
    {
        $config = require HORIZOM_ROOT . '/config/'. $name .'.php';
        self::$settings = array_merge(self::$settings, $config);

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
     * Load the Eloquent library for the application.
     */
    public function withEloquent(): self
    {
        $config = require HORIZOM_ROOT . '/config/database.php';
        $connections = $config['database.connections'];
        $name = $config['database.default'];

        $capsule = new DatabaseManager();
        $capsule->addConnection($connections[$name], $name);
        $capsule->bootEloquent();
        $capsule->setAsGlobal();

        return $this;
    }

    /**
     * Set default namespace for route-callbacks
     */
    public function setDefaultNamespace(string $namespace): self
    {
        $this->defaultNamespace = $namespace;
        return $this;
    }

    /**
     * Run The Application
     */
    public function run()
    {
        $request = Request::fromGlobals();
        $router = $this->router->getRouter();

        $routerDispatcher = new \Horizom\Routing\Middleware\Dispatcher([
            new ErrorHandlingMiddleware(new ErrorHandlerMiddleware()),
            $router
        ]);

        $this->dispatcher->dispatch($request);
        $response = $routerDispatcher->handle($request);

        Response::fromInstance($response)->emit();
    }

    /**
     * Get Configuration Values
     */
    public static function config()
    {
        return self::$settings;
    }

    private function containerDefinitions()
    {
        return [

            'db.host' => \DI\env('DB_HOST', 'localhost'),
            'db.name' => \DI\env('DB_NAME', 'test'),
            'db.username' => \DI\env('DB_USERNAME', 'root'),
            'db.password' => \DI\env('DB_PASSWORD', 'root'),

            \Horizom\Http\Response::class => \DI\factory(function () {
                return new Response();
            })

        ];
    }
}
