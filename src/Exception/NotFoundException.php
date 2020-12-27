<?php

declare(strict_types=1);

namespace Horizom\Exception;

use RuntimeException;

class NotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Not Found', 0, null);
    }
}
