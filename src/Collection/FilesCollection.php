<?php

namespace Horizom\Collection;

use Illuminate\Support\Collection;

/**
 * A Collection for "$_FILES" like data
 */
class FilesCollection extends Collection
{
    /**
     * Select uloaded $_FILES row
     */
    public function row(string $name)
    {
        # code...

        return $this;
    }

    public function path()
    {
        # code...
    }

    public function extension()
    {
        # code...
    }

    public function hasFile()
    {
        # code...
    }

    public function size()
    {
        # code...
    }

    public function getError()
    {
        # code...
    }

    public function move($directory, $name = null)
    {
        # code...
    }

    public function isValid()
    {
        # code...
    }
}
