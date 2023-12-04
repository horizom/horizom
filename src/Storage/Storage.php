<?php

namespace Horizom\Storage;

class Storage
{
    private static $configs = [];

    private static $default = 'local';

    public function __construct(string $default = null, array $configs = [])
    {
        self::$configs = !empty($configs) ? $configs : config('filesystems.disks');
        self::$default = $default ?? config('filesystems.default');

        if (self::$configs == null || count(self::$configs) == 0) {
            throw new StorageConfigException('Missing storage "filesystems" config.');
        }
    }

    /**
     * Get filesystem disk
     */
    public static function disk(string $disk = null): FilesystemAdapter
    {
        $disk = $disk ?? self::$default;
        $local = new LocalStorage(self::$configs['local']);

        switch ($disk) {
            case 'local':
                return $local;
                break;
            case 's3':
                // TODO: Implement S3 storage
                break;
            case 'cloudstorage':
                // TODO: Implement Cloudstorage storage
                break;
            case 'dropbox':
                // TODO: Implement Dropbox storage
                break;
            case 'ftp':
                // TODO: Implement FTP storage
                break;
            default:
                return $local;
                break;
        }
    }

    /**
     * Write a file from a string or a resource
     */
    public static function write(string $path, $contents, string $visibility = null)
    {
        self::disk()->write($path, $contents, $visibility);
    }

    /**
     * Read file contents
     */
    public static function read(string $path)
    {
        return self::disk()->read($path);
    }

    /**
     * Read a file as a stream
     */
    public static function stream(string $path)
    {
        return self::disk()->stream($path);
    }

    /**
     * Delete a file
     */
    public static function delete(string $path)
    {
        self::disk()->delete($path);
    }

    /**
     * Checks whether a file exists
     */
    public static function exists(string $path)
    {
        return self::disk()->exists($path);
    }

    /**
     * Get file mime type
     */
    public static function mime(string $path)
    {
        return self::disk()->mime($path);
    }

    /**
     * Get file size
     */
    public static function size(string $path)
    {
        return self::disk()->size($path);
    }

    /**
     * Get file last modified time
     */
    public static function lastModified(string $path)
    {
        return self::disk()->lastModified($path);
    }

    /**
     * Set or Get visibility of a file
     */
    public static function visibility(string $path, string $visibility = null)
    {
        self::disk()->visibility($path, $visibility);
    }

    /**
     * Copy a file to a new location
     */
    public static function copy(string $source, string $destination)
    {
        self::disk()->copy($source, $destination);
    }

    /**
     * Move a file to a new location
     */
    public static function move(string $source, string $destination)
    {
        self::disk()->move($source, $destination);
    }

    /**
     * List all files from a path
     */
    public static function files(string $path = null, bool $recursive = false)
    {
        return self::disk()->files($path, $recursive);
    }

    /**
     * List all directories from a path
     */
    public static function directories(string $path = null, bool $recursive = false)
    {
        return self::disk()->directories($path, $recursive);
    }

    /**
     * List all contents of a path
     */
    public static function all(string $path = null, bool $recursive = false)
    {
        return self::disk()->all($path, $recursive);
    }

    /**
     * List all contents of a path
     */
    public static function contents(string $path = null, bool $recursive = false)
    {
        return self::disk()->contents($path, $recursive);
    }

    /**
     * Create a directory
     */
    public static function createDirectory(string $location)
    {
        self::disk()->createDirectory($location);
    }

    /**
     * Delete a directory
     */
    public static function deleteDirectory(string $location)
    {
        self::disk()->deleteDirectory($location);
    }

    /**
     * Checks whether a directory exists
     */
    public static function directoryExists(string $path)
    {
        return self::disk()->directoryExists($path);
    }

    /**
     * Checks whether a file or directory exists
     */
    public static function has(string $path)
    {
        return self::disk()->has($path);
    }

    /**
     * Get file or directory path
     */
    public static function path(string $path)
    {
        return self::disk()->path($path);
    }

    /**
     * Get the URL for the file at the given path.
     */
    public static function url(string $path)
    {
        return self::disk()->url($path);
    }

    /**
     * Get default filesystem adapter
     */
    public static function getAdapter()
    {
        return self::disk()->getAdapter();
    }
}
