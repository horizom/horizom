<?php

namespace Horizom\Collection;

/**
 * A DataCollection for "$_FILES" like data
 */
class FilesDataCollection extends DataCollection
{
    /**
     * Select uloaded $_FILES row
     * @return FilesDataCollection
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
