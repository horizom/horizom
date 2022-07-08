<?php

namespace Horizom\Http;

use Horizom\Http\Exceptions\FileNotFoundException;

class UploadedFile extends \Symfony\Component\HttpFoundation\File\UploadedFile
{
    use FileHelpers;

    /**
     * Get the contents of the uploaded file.
     *
     * @return false|string
     *
     * @throws FileNotFoundException
     */
    public function get()
    {
        if (!$this->isValid()) {
            throw new FileNotFoundException("File does not exist at path {$this->getPathname()}.");
        }

        return file_get_contents($this->getPathname());
    }

    /**
     * Get the file's extension supplied by the client.
     *
     * @return string
     */
    public function clientExtension()
    {
        return $this->guessClientExtension();
    }

    /**
     * Create a new file instance from a base instance.
     *
     * @param  UploadedFile  $file
     * @param  bool  $test
     * @return static
     */
    public static function createFromBase(UploadedFile $file, $test = false)
    {
        return $file instanceof static ? $file : new static(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getError(),
            $test
        );
    }

    /**
     * Parse and format the given options.
     *
     * @param  array|string  $options
     * @return array
     */
    protected function parseOptions($options)
    {
        if (is_string($options)) {
            $options = ['disk' => $options];
        }

        return $options;
    }
}
