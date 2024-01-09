<?php

namespace Horizom\Core\Bootstrap;

use Horizom\Core\App;

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param  App  $app
     * @return void
     */
    public function bootstrap($app)
    {
        $app->boot();
    }
}
