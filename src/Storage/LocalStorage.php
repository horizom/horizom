<?php

namespace Horizom\Storage;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

class LocalStorage implements FilesystemAdapter
{
    private $config = [];

    /**
     * @var array
     */
    private $permissions = [
        'file' => [
            'public' => 0644,
            'private' => 0604,
        ],
        'dir' => [
            'public' => 0755,
            'private' => 7604,
        ],
    ];

    /**
     * @var Filesystem
     */
    private $adapter;

    /**
     * @throws StorageConfigException
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        if (!isset($config['root']) || !isset($config['visibility'])) {
            throw new StorageConfigException('Missing storage "root" and "visibility" config for local storage.');
        }

        if ($config['visibility'] == 'public') {
            $visibility = PortableVisibilityConverter::fromArray($this->permissions, Visibility::PUBLIC);
        } else if ($config['visibility'] == 'private') {
            $visibility = PortableVisibilityConverter::fromArray($this->permissions, Visibility::PRIVATE);
        } else {
            throw new StorageConfigException('Invalid storage "local.visibility" config.');
        }

        $adapter = new LocalFilesystemAdapter($config['root'], $visibility);

        $this->adapter = new Filesystem($adapter);
    }

    /**
     * Write a file from a string or a resource
     */
    public function write(string $path, $contents, string $visibility = null)
    {
        if ($visibility === null) {
            if (!isset($this->config['visibility'])) {
                throw new StorageConfigException('Missing storage "visibility" config.');
            }

            $visibility = $this->config['visibility'];
        }

        $this->config = ['visibility' => $visibility];

        if (gettype($contents) == 'string') {
            $this->adapter->write($path, $contents, $this->config);
        } else if (gettype($contents) == 'resource') {
            $this->adapter->writeStream($path, $contents, $this->config);
        }
    }

    /**
     * Read file contents
     */
    public function read(string $path)
    {
        return $this->adapter->read($path);
    }

    /**
     * Read a file as a stream
     */
    public function stream(string $path)
    {
        return $this->adapter->readStream($path);
    }

    /**
     * Delete a file
     */
    public function delete(string $path)
    {
        $this->adapter->delete($path);
    }

    /**
     * Checks whether a file exists
     */
    public function exists(string $path)
    {
        return $this->adapter->fileExists($path);
    }

    /**
     * Get file mime type
     */
    public function mime(string $path)
    {
        return $this->adapter->mimeType($path);
    }

    /**
     * Get file last modified time
     */
    public function lastModified(string $path)
    {
        return $this->adapter->lastModified($path);
    }

    /**
     * Get file size
     */
    public function size(string $path)
    {
        return $this->adapter->fileSize($path);
    }

    /**
     * Copy a file to a new location
     */
    public function copy(string $source, string $destination)
    {
        $this->adapter->copy($source, $destination);
    }

    /**
     * Move a file to a new location
     */
    public function move(string $source, string $destination)
    {
        $this->adapter->move($source, $destination);
    }

    /**
     * Create a directory
     */
    public function createDirectory(string $location)
    {
        return $this->adapter->createDirectory($location);
    }

    /**
     * Delete a directory
     */
    public function deleteDirectory(string $location)
    {
        $this->adapter->deleteDirectory($location);
    }

    /**
     * Checks whether a directory exists
     */
    public function directoryExists(string $path)
    {
        return $this->adapter->directoryExists($path);
    }

    /**
     * Set or Get visibility of a file
     */
    public function visibility(string $path, string $visibility = null)
    {
        if ($visibility != null) {
            $this->adapter->setVisibility($path, $visibility);
        }

        return $this->adapter->visibility($path);
    }

    /**
     * Checks whether a file or directory exists
     */
    public function has(string $path)
    {
        return $this->adapter->has($path);
    }

    /**
     * List all contents of a path
     */
    public function contents(string $location, bool $recursive = false)
    {
        return $this->adapter->listContents($location, $recursive);
    }

    /**
     * List all contents of a path
     */
    public function all(string $location, bool $recursive = false)
    {
        return $this->contents($location, $recursive);
    }

    /**
     * List all files from a path
     */
    public function files(string $location, bool $recursive = false)
    {
        $listing = $this->contents($location, $recursive);
        $items = [];

        foreach ($listing as $item) {
            if ($item instanceof FileAttributes) {
                $items[] = $item;
            }
        }
    }

    /**
     * List all directories from a path
     */
    public function directories(string $location, bool $recursive = false)
    {
        $listing = $this->contents($location, $recursive);
        $items = [];

        foreach ($listing as $item) {
            if ($item instanceof DirectoryAttributes) {
                $items[] = $item;
            }
        }
    }

    /**
     * Get the full path of a file or directory.
     */
    public function path(string $path): string
    {
        if (!isset($this->config['root'])) {
            throw new StorageConfigException('Missing storage "root" config.');
        }

        return $this->config['root'] . '/' . $path;
    }

    /**
     * Get file url
     */
    public function url(string $path): string
    {
        if (!isset($this->config['url'])) {
            throw new StorageConfigException('Missing storage "url" config.');
        }

        return trim($this->config['url'], '/') . "/" . $path;
    }

    /**
     * Get disk filesystem instance
     */
    public function getAdapter(): Filesystem
    {
        return $this->adapter;
    }
}
