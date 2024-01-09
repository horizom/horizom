<?php

namespace Horizom\Core\Bootstrap;

class LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Horizom\Core\App  $app
     * @return void
     */
    public function bootstrap($app)
    {
        $app->loadEnvironmentFrom('.env');
    }

    /**
     * Get the environment file to be loaded during bootstrapping.
     *
     * @return string
     */
    public function environmentFile()
    {
        return '.env';
    }
}