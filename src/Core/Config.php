<?php

namespace Horizom\Core;

use Illuminate\Support\Collection;

class Config extends Collection
{
    public function __construct(array $items = [])
    {
        parent::__construct(array_merge($this->getDefaults(), $items));
    }

    private function getDefaults()
    {
        return [
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
    }
}
