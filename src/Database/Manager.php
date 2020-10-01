<?php

namespace Horizom\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class Manager
{
    public function __construct(array $connections, string $name)
    {
        $capsule = new Capsule();

        foreach ($connections as $key => $conn) {
            $capsule->addConnection($conn, $key);
        }

        $capsule->bootEloquent();
        $capsule->setAsGlobal();
    }
}
