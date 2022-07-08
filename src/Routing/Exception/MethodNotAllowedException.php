<?php

namespace Horizom\Routing\Exception;

class MethodNotAllowedException extends RoutingException
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
