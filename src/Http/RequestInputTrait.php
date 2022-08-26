<?php

namespace Horizom\Http;

use Horizom\Http\Collection\FileCollection;
use Horizom\Http\Collection\ServerCollection;
use Illuminate\Support\Collection;

trait RequestInputTrait
{
    /**
     * Access all of the user POST input
     *
     * @return mixed|Collection
     */
    public function post(string $name = null, $default = null)
    {
        $post = new Collection($_POST);

        if ($name) {
            return $post->get($name, $default);
        }

        return $post;
    }

    /**
     * Access values from entire request payload (including the query string)
     *
     * @return mixed|Collection
     */
    public function query(string $name = null, $default = null)
    {
        $parse = $this->getUri()->getQuery();
        $queries = $this->parseQueryParams($parse);
        $query = new Collection($queries);

        if ($name) {
            return $query->get($name, $default);
        }

        return $query;
    }

    /**
     * Access uploaded files from the request
     *
     * @return mixed|FileCollection
     */
    public function files(string $name = null)
    {
        $files = new FileCollection($_FILES);

        if ($name) {
            return $files->get($name);
        }

        return $files;
    }

    /**
     * Access all of the user COOKIE input
     *
     * @return mixed|Collection
     */
    public function cookie(string $name = null, $default = null)
    {
        $cookie = new Collection($_COOKIE);

        if ($name) {
            return $cookie->get($name, $default);
        }

        return $cookie;
    }

    /**
     * Access all server params
     *
     * @return mixed|ServerCollection
     */
    public function server(string $name = null, $default = null)
    {
        $server =  new ServerCollection($_SERVER);

        if ($name) {
            return $server->get($name, $default);
        }

        return $server;
    }

    /**
     * Get an collection of all input data ($_GET, $_POST, $_FILES, ...)
     */
    public function collect()
    {
        $post = $this->post()->all();
        $query = $this->query()->all();
        $files = $this->files()->all();

        $putdata = fopen("php://input", "r");
        $putString = fread($putdata, 1024);
        fclose($putdata);

        $put = $this->parseQueryParams($putString);
        $all = array_merge($query, $post, $put, $files);
        $collect = array_map(fn ($i) => urldecode($i), $all);

        return new Collection($collect);
    }

    /**
     * Get all input data as an array.
     */
    public function all()
    {
        return $this->collect()->all();
    }

    /**
     * Retrieve a single input item from the request.
     */
    public function input($key = null, $default = null)
    {
        $input = $this->collect();

        if (is_null($key)) {
            return $input->all();
        }

        return $input->get($key, $default);
    }

    /**
     * Check if the keys exists in the input data.
     */
    public function has($key)
    {
        return $this->collect()->has($key);
    }

    /**
     * Check if the keys missing from the input data.
     */
    public function missing($key)
    {
        return !$this->has($key);
    }

    /**
     * Get the items with the specified keys.
     */
    public function only($keys)
    {
        return $this->collect()->only($keys);
    }

    /**
     * Get the items except for those with the specified keys.
     */
    public function except($keys)
    {
        return $this->collect()->except($keys);
    }

    /**
     * Get an item from the input data as a string.
     */
    public function string(string $key = null, $default = null)
    {
        return (string) $this->collect()->get($key, $default);
    }

    /**
     * Get an item from the input data as an integer.
     */
    public function integer(string $key = null, $default = null)
    {
        return (int) $this->collect()->get($key, $default);
    }

    /**
     * Get an item from the input data as a float.
     */
    public function float(string $key = null, $default = null)
    {
        return (float) $this->collect()->get($key, $default);
    }

    /**
     * Get an item from the input data as a boolean.
     */
    public function boolean(string $key = null, $default = null)
    {
        return (bool) $this->collect()->get($key, $default);
    }

    /**
     * Merge the input data with the given array.
     */
    public function merge(array $input)
    {
        return $this->collect()->merge($input);
    }

    /**
     * Check if the input data as a file with the given key.
     */
    public function hasFile(string $key)
    {
        return $this->files()->has($key);
    }

    private function parseQueryParams(string $parse)
    {
        $params = [];

        if ($parse) {
            foreach (explode('&', $parse) as $v) {
                $param = explode('=', $v);
                $params[$param[0]] = $param[1];
            }
        }

        return $params;
    }
}
