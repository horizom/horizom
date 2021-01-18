<?php

namespace Horizom;

use Closure;
use InvalidArgumentException;
use Horizom\Http\Request;
use Horizom\Routing\RouteCollectorInterface;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * The route collection.
     *
     * @var \Horizom\Routing\RouteCollectorInterface
     */
    protected $routes;

    /**
     * The request instance.
     *
     * @var \Horizom\Http\Request
     */
    protected $request;

    /**
     * The asset root URL.
     *
     * @var string
     */
    protected $assetRoot;

    /**
     * Create a new URL Generator instance.
     *
     * @param  \Illuminate\Routing\RouteCollectionInterface  $routes
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $assetRoot
     * @return void
     */
    public function __construct(RouteCollectorInterface $routes, Request $request, $assetRoot = null)
    {
        $this->routes = $routes;
        $this->assetRoot = $assetRoot;

        $this->setRequest($request);
    }

    /**
     * Get the full URL for the current request.
     *
     * @return string
     */
    public function full()
    {
        return $this->request->fullUrl();
    }

    /**
     * Get the current URL for the request.
     *
     * @return string
     */
    public function current()
    {
        return $this->to($this->request->getPathInfo());
    }
}