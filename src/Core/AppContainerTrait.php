<?php

declare(strict_types=1);

namespace Horizom\Core;

use Illuminate\Support\Arr;

/**
 * @property Container $container
 */
trait AppContainerTrait
{
    /**
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * @var array
     */
    public function call($callback, array $parameters = [])
    {
        return $this->container->call($callback, $parameters);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     */
    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function make(string $name, array $parameters = null)
    {
        return $this->container->make($name, $parameters);
    }

    public function singleton(string $abstract, $concrete = null)
    {
        return $this->container->singleton($abstract, $concrete);
    }

    public function bind(string $abstract, $concrete = null)
    {
        return $this->container->bind($abstract, $concrete);
    }

    public function instance(string $abstract, $instance)
    {
        return $this->container->instance($abstract, $instance);
    }

    /**
     * Register a service provider with the application.
     *
     * @param  ServiceProvider|string  $provider
     * @param  bool  $force
     * @return ServiceProvider
     */
    public function register($provider, $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        foreach ($provider->when() as $event) {
            $this->registerDeferredProvider($provider, $event);
        }

        $this->markAsRegistered($provider);

        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  ServiceProvider|string  $provider
     * @return ServiceProvider|null
     */
    public function getProvider($provider)
    {
        return array_values($this->getProviders($provider))[0] ?? null;
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param  ServiceProvider|string  $provider
     * @return array
     */
    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, fn($value) => $value instanceof $name);
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
     * Mark the given provider as registered.
     *
     * @param  ServiceProvider  $provider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Boot the given service provider.
     *
     * @param  ServiceProvider  $provider
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    /**
     * Register a new boot listener.
     *
     * @param  callable  $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  callable  $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        array_walk($this->serviceProviders, fn($p) => $this->bootProvider($p));

        $this->booted = true;
    }
}
