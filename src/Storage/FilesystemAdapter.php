<?php

namespace Horizom\Storage;

use League\Flysystem\Filesystem;

interface FilesystemAdapter
{
    /**
     * @param string $path
     * @param resource|string $contents
     * @param string $visibility `public` or `private`
     * 
     * @throws UnableToWriteFile
     * @throws FilesystemException
     * 
     * @return void
     */
    public function write(string $path, $contents, string $visibility = null);

    /**
     * @throws UnableToReadFile
     * @throws FilesystemException
     * @return string
     */
    public function read(string $path);

    /**
     * @throws UnableToReadFile
     * @throws FilesystemException
     * @return resource
     */
    public function stream(string $path);

    /**
     * @throws UnableToDeleteFile
     * @throws FilesystemException
     * @return void
     */
    public function delete(string $path);

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     * @return bool
     */
    public function exists(string $path);

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     * @return string
     */
    public function mime(string $path);

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     * @return int
     */
    public function size(string $path);

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     * @return int
     */
    public function lastModified(string $path);

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemException
     * @return void
     */
    public function move(string $source, string $destination);

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemException
     * @return void
     */
    public function copy(string $source, string $destination);

    /**
     * @throws UnableToCreateDirectory
     * @throws FilesystemException
     * @return void
     */
    public function createDirectory(string $path);

    /**
     * @throws UnableToDeleteDirectory
     * @throws FilesystemException
     * @return void
     */
    public function deleteDirectory(string $path);

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     * @return bool
     */
    public function directoryExists(string $path);

    /**
     * @throws UnableToSetVisibility
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     * @return string
     */
    public function visibility(string $path, string $visibility = null);

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     * @return bool
     */
    public function has(string $path);

    /**
     * @throws FilesystemException
     * @return iterable<StorageAttributes>
     */
    public function contents(string $location, bool $recursive);

    /**
     * @throws FilesystemException
     * @return iterable<StorageAttributes>
     */
    public function all(string $location, bool $recursive);

    /**
     * @throws FilesystemException
     * @return FileAttributes[]
     */
    public function files(string $location, bool $recursive = false);

    /**
     * @throws FilesystemException
     * @return DirectoryAttributes[]
     */
    public function directories(string $location, bool $recursive = false);

    /**
     * @throws StorageConfigException
     * @return string
     */
    public function path(string $path);

    /**
     * @throws StorageConfigException
     * @return string
     */
    public function url(string $path);

    /**
     * @return Filesystem
     */
    public function getAdapter();
}
