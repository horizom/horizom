<?php

declare(strict_types=1);

namespace Horizom\Exception;

use RuntimeException;

class MethodNotAllowedException extends RuntimeException
{
    /**
     * @var string[]
     */
    private $allowedMethods;

    /**
     * @param string[] $allowedMethods
     */
    public function __construct(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;

        parent::__construct('Method Not Allowed');
    }

    /**
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
