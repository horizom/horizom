<?php

namespace Horizom\Matchers;

trait NegativeResultTrait
{
    private $result = true;

    private function getValue(string $value)
    {
        if ($value[0] === '!') {
            $this->result = false;
            return substr($value, 1);
        }

        return $value;
    }
}
