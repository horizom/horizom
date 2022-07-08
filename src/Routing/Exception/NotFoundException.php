<?php

namespace Horizom\Routing\Exception;

class NotFoundException extends RoutingException
{
    public function __construct()
    {
        parent::__construct('Not Found', 0, null);
    }
}
