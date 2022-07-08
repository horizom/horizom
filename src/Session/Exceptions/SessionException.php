<?php

namespace Horizom\Session\Exceptions;

class SessionException extends \Exception
{
    public function __construct(string $message = 'Unknown error')
    {
        parent::__construct(rtrim($message, '.') . '.');
    }
}
