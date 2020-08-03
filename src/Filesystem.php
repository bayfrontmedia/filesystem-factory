<?php

/**
 * @package filesystem-factory
 * @link https://github.com/bayfrontmedia/filesystem-factory
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\Filesystem;

use Exception;
use Bayfront\ArrayHelpers\Arr;
use League\Flysystem\Filesystem as Flysystem;

class Filesystem
{

    private $default_disk_name = 'default';

    private $adapters_namespace = 'Bayfront\\Filesystem\\Adapters\\';

    private $cache_namespace = 'Bayfront\\Filesystem\\Cache\\';

    private $config; // Storage config array

    private $current_disk; // Current disk name

    /*
     * Revert back to default disk after _getDisk() is called
     *
     * This is updated via the $make_default parameter of disk()
     */

    private $revert_disk_after_use = true;

    /**
     * Constructor
     *
     * @param array $config (Storage configuration array)
     *
     * @return void
     *
     * @throws Exceptions\ConfigurationException
     *
     */

    public function __construct(array $config)
    {

        if (!isset($config[$this->default_disk_name])) { // Must have a "default" disk on the array

            throw new Exceptions\ConfigurationException('Invalid storage configuration');

        }

        $this->config = $config;

        $this->current_disk = $this->default_disk_name;

    }

    private $filesystems = []; // Active filesystem instances

    /**
     * Returns current disk's filesystem object, then resets current disk to default
     *
     * @return Flysystem
     *
     */

    private function _getDisk(): Flysystem
    {

        $disk = $this->current_disk;

        if (!isset($this->filesystems[$disk])) { // This can happen when disk() has never been explicitly used

            $this->disk($disk); // Create filesystem object

        }

        if (true === $this->revert_disk_after_use) {

            $this->current_disk = $this->default_disk_name; // Revert disk

        }

        return $this->filesystems[$disk];

    }

    /**
     * Returns textual representation of visibility from boolean value
     *
     * @param bool $bool
     *
     * @return string
     */

    private function _boolToVisibility(bool $bool): string
    {
        return (true === $bool) ? 'public' : 'private';
    }

    /**
     * Sets the current disk, creating a new filesystem object if not already existing
     *
     * @param string $name
     * @param bool $make_default
     *
     * @return self
     *
     * @throws Exceptions\ConfigurationException
     * @throws Exceptions\DiskException
     */

    public function disk(string $name, bool $make_default = false): self
    {

        if (isset($this->filesystems[$name])) { // If filesystem is already created

            $this->current_disk = $name; // Update current disk name

            if (true === $make_default) {

                $this->revert_disk_after_use = false;

            }

            return $this;

        }

        // Create new filesystem object

        // Check validity

        if (!isset($this->config[$name])
            || Arr::isMissing($this->config[$name], [
                'adapter'
            ])) {

            throw new Exceptions\ConfigurationException('Invalid disk configuration');

        }

        if (!class_exists($this->adapters_namespace . $this->config[$name]['adapter'])) {

            throw new Exceptions\DiskException('Disk adapter does not exist');

        }

        // Create object

        /** @var $adapter_class AdapterInterface */

        $adapter_class = $this->adapters_namespace . $this->config[$name]['adapter'];

        try {

            $adapter = $adapter_class::create($this->config[$name]);

            // Check for cache

            if (isset($this->config[$name]['cache']['location'])
                && class_exists($this->cache_namespace . $this->config[$name]['cache']['location'])) {

                /** @var $cache_class CacheInterface */

                $cache_class = $this->cache_namespace . $this->config[$name]['cache']['location'];

                $adapter = $cache_class::create($this->config[$name]['cache'], $adapter);

            }

            $this->filesystems[$name] = new Flysystem($adapter); // Create new filesystem instance

            $this->current_disk = $name; // Update current disk name

            if (true === $make_default) {

                $this->revert_disk_after_use = false;

            }

            return $this;

        } catch (Exception $e) {

            throw new Exceptions\DiskException($e->getMessage());

        }

    }

    /**
     * Returns name of current disk
     *
     * @return string
     */

    public function getCurrentDisk(): string
    {
        return $this->current_disk;
    }

    /*
     * ############################################################
     * Files and directories
     * ############################################################
     */

    /**
     * Write/overwrite file
     *
     * NOTE: This method uses Flysystem's put method
     *
     * @param string $file
     * @param string $contents
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws Exceptions\FileWriteException
     *
     */

    public function write(string $file, string $contents, bool $public = false): void
    {

        try {

            $write = $this->_getDisk()->put($file, $contents, ['visibility' => $this->_boolToVisibility($public)]);

            if ($write) {

                return;

            }

            throw new Exceptions\FileWriteException('Unable to write');

        } catch (Exception $e) {

            throw new Exceptions\FileWriteException($e->getMessage());

        }

    }

    /**
     * Write/overwrite file using a stream
     *
     * NOTE: This method uses Flysystem's putStream method
     *
     * @param string $file
     * @param resource $resource
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws Exceptions\FileWriteException
     *
     */

    public function writeStream(string $file, $resource, bool $public = false): void
    {

        try {

            $write = $this->_getDisk()->putStream($file, $resource, ['visibility' => $this->_boolToVisibility($public)]);

            if ($write) {

                return;

            }

            throw new Exceptions\FileWriteException('Unable to write stream');

        } catch (Exception $e) {

            throw new Exceptions\FileWriteException($e->getMessage());

        }

    }

    /**
     * Prepends existing file with given contents
     *
     * @param string $file
     * @param string $contents
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws Exceptions\FileWriteException
     *
     */

    public function prepend(string $file, string $contents, bool $public = false): void
    {

        try {

            $disk = $this->_getDisk();

            $existing = $disk->read($file);

            if ($existing) {

                $put = $disk->put($file, $contents . $existing, ['visibility' => $this->_boolToVisibility($public)]);

                if ($put) {

                    return;

                }

            }

            throw new Exceptions\FileWriteException('Unable to prepend');

        } catch (Exception $e) {

            throw new Exceptions\FileWriteException($e->getMessage());

        }

    }

    /**
     * Appends existing file with given contents
     *
     * @param string $file
     * @param string $contents
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws Exceptions\FileWriteException
     *
     */

    public function append(string $file, string $contents, bool $public = false): void
    {

        try {

            $disk = $this->_getDisk();

            $existing = $disk->read($file);

            if ($existing) {

                $put = $disk->put($file, $existing . $contents, ['visibility' => $this->_boolToVisibility($public)]);

                if ($put) {

                    return;

                }

            }

            throw new Exceptions\FileWriteException('Unable to append');

        } catch (Exception $e) {

            throw new Exceptions\FileWriteException($e->getMessage());

        }

    }

    /**
     * Checks if a given file or directory exists
     *
     * NOTE: Behavior may be inconsistent with directories, depending on the adapter being used
     *
     * @param string $path
     *
     * @return bool
     */

    public function exists(string $path): bool
    {
        return $this->_getDisk()->has($path);
    }

    /**
     * Checks if a given file or directory does not exist
     *
     * NOTE: Behavior may be inconsistent with directories, depending on the adapter being used
     *
     * @param string $path
     *
     * @return bool
     */

    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    /**
     * Renames a file or folder
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     *
     * @throws Exceptions\FileRenameException
     *
     */

    public function rename(string $from, string $to): void
    {

        try {

            $rename = $this->_getDisk()->rename($from, $to);

            if ($rename) {

                return;

            }

            throw new Exceptions\FileRenameException('Unable to rename');

        } catch (Exception $e) {

            throw new Exceptions\FileRenameException($e->getMessage());

        }

    }

    /**
     * Copies file from one location to another
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     *
     * @throws Exceptions\FileCopyException
     *
     */

    public function copy(string $from, string $to): void
    {

        try {

            $copy = $this->_getDisk()->copy($from, $to);

            if ($copy) {

                return;

            }

            throw new Exceptions\FileCopyException('Unable to copy');

        } catch (Exception $e) {

            throw new Exceptions\FileCopyException($e->getMessage());

        }

    }

    /**
     * Moves file from one location to another
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     *
     * @throws Exceptions\FileMoveException
     *
     */

    public function move(string $from, string $to): void
    {

        try {

            $disk = $this->_getDisk();

            if ($disk->copy($from, $to)) {

                if ($disk->delete($from)) {

                    return;

                }

            }

            throw new Exceptions\FileMoveException('Unable to move');

        } catch (Exception $e) {

            throw new Exceptions\FileMoveException($e->getMessage());

        }

    }

    /**
     * Returns the contents of a file
     *
     * @param string $file
     *
     * @return string
     *
     * @throws Exceptions\FileReadException
     *
     */

    public function read(string $file): string
    {

        try {

            $get = $this->_getDisk()->read($file);

            if ($get) {

                return $get;

            }

            throw new Exceptions\FileReadException('Unable to read');

        } catch (Exception $e) {

            throw new Exceptions\FileReadException($e->getMessage());

        }

    }

    /**
     * Returns resource
     *
     * @param string $file
     *
     * @return resource
     *
     * @throws Exceptions\FileReadException
     *
     */

    public function readStream(string $file)
    {

        try {

            $get = $this->_getDisk()->readStream($file);

            if ($get) {

                return $get;

            }

            throw new Exceptions\FileReadException('Unable to read stream');

        } catch (Exception $e) {

            throw new Exceptions\FileReadException($e->getMessage());

        }

    }

    /**
     * Returns the contents of file, then deletes it
     *
     * @param string $file
     *
     * @return string
     *
     * @throws Exceptions\FileReadException
     *
     */

    public function readAndDelete(string $file): string
    {

        try {

            $get = $this->_getDisk()->readAndDelete($file);

            if ($get) {

                return $get;

            }

            throw new Exceptions\FileReadException('Unable to read and delete');

        } catch (Exception $e) {

            throw new Exceptions\FileReadException($e->getMessage());

        }

    }

    /**
     * Deletes a file
     *
     * @param string $file
     *
     * @return void
     *
     * @throws Exceptions\FileDeleteException
     *
     */

    public function delete(string $file): void
    {

        try {

            $delete = $this->_getDisk()->delete($file);

            if (!$delete) {

                throw new Exceptions\FileDeleteException('Unable to delete');

            }

        } catch (Exception $e) {

            throw new Exceptions\FileDeleteException($e->getMessage());

        }

    }

    /**
     * Create a directory
     *
     * @param string $path
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws Exceptions\DirectoryCreateException
     *
     */

    public function createDir(string $path, bool $public = false): void
    {

        try {

            $dir = $this->_getDisk()->createDir($path, ['visibility' => $this->_boolToVisibility($public)]);

            if ($dir) {

                return;

            }

            throw new Exceptions\DirectoryCreateException('Unable to create directory');

        } catch (Exception $e) {

            throw new Exceptions\DirectoryCreateException($e->getMessage());

        }

    }

    /**
     * Delete a directory
     *
     * @param string $path
     *
     * @return void
     *
     * @throws Exceptions\DirectoryDeleteException
     *
     */

    public function deleteDir(string $path): void
    {

        try {

            $dir = $this->_getDisk()->deleteDir($path);

            if ($dir) {

                return;

            }

            throw new Exceptions\DirectoryDeleteException('Unable to delete directory');

        } catch (Exception $e) {

            throw new Exceptions\DirectoryDeleteException($e->getMessage());

        }

    }

    /**
     * List all contents of a directory
     *
     * NOTE: Returned array keys may differ depending on the adapter used
     *
     * @param string $path
     * @param bool $recursive
     *
     * @return array
     */

    public function listContents(string $path, bool $recursive = false): array
    {
        return $this->_getDisk()->listContents($path, $recursive);
    }

    /**
     * List all files in a directory
     *
     * NOTE: Returned array keys may differ depending on the adapter used
     *
     * @param string $path
     * @param bool $recursive
     * @param string|array $extensions (List only files with given extension(s))
     *
     * @return array
     */

    public function listFiles(string $path, bool $recursive = false, $extensions = []): array
    {

        $contents = $this->listContents($path, $recursive);

        $extensions = (array)$extensions;

        foreach ($contents as $k => $content) {

            if (isset($content['type']) && $content['type'] != 'file'
                || !empty($extensions) && isset($content['extension']) && !in_array($content['extension'], $extensions)) {

                unset($contents[$k]);

            }

        }

        return array_values($contents);

    }

    /**
     * List all files in a directory except files with given extension(s)
     *
     * NOTE: Returned array keys may differ depending on the adapter used
     *
     * @param string $path
     * @param bool $recursive
     * @param string|array $extensions (Extension(s) to exclude)
     *
     * @return array
     */

    public function listFilesExcept(string $path, bool $recursive = false, $extensions = []): array
    {

        $contents = $this->listContents($path, $recursive);

        $extensions = (array)$extensions;

        foreach ($contents as $k => $content) {

            if (isset($content['type']) && $content['type'] != 'file'
                || !empty($extensions) && isset($content['extension']) && in_array($content['extension'], $extensions)) {

                unset($contents[$k]);

            }

        }

        return array_values($contents);

    }

    /**
     * List all directories in a path
     *
     * NOTE: Returned array keys may differ depending on the adapter used
     *
     * @param string $path
     * @param bool $recursive
     *
     * @return array
     */

    public function listDirs(string $path, bool $recursive = false): array
    {

        $contents = $this->listContents($path, $recursive);

        foreach ($contents as $k => $content) {

            if (isset($content['type']) && $content['type'] != 'dir') {

                unset($contents[$k]);

            }
        }

        return array_values($contents);

    }

    /*
     * ############################################################
     * File meta
     * ############################################################
     */

    /**
     * Returns the visibility of a given file or directory
     *
     * @param string $path
     *
     * @return string ("public" or "private")
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function getVisibility(string $path): string
    {

        try {

            return $this->_getDisk()->getVisibility($path);

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Checks if the visibility of a given file or directory is "public"
     *
     * @param string $path
     *
     * @return bool
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function isPublic(string $path): bool
    {

        try {

            return $this->_getDisk()->getVisibility($path) == 'public';

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Checks if the visibility of a given file or directory is "private"
     *
     * @param string $path
     *
     * @return bool
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function isPrivate(string $path): bool
    {

        try {

            return $this->_getDisk()->getVisibility($path) == 'private';

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Sets the visibility of a given file or directory to "public"
     *
     * @param string $path
     *
     * @return void
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function setPublic(string $path): void
    {

        try {

            $visibility = $this->_getDisk()->setVisibility($path, 'public');

            if (!$visibility) {

                throw new Exceptions\FileMetadataException('Unable to set visibility as public');

            }

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Sets the visibility of a given file or directory to "private"
     *
     * @param string $path
     *
     * @return void
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function setPrivate(string $path): void
    {

        try {

            $visibility = $this->_getDisk()->setVisibility($path, 'private');

            if (!$visibility) {

                throw new Exceptions\FileMetadataException('Unable to set visibility as private');

            }

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Sets the visibility of a given file or directory
     *
     * @param string $path
     * @param string $visibility ("public" or "private")
     *
     * @return void
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function setVisibility(string $path, string $visibility): void
    {

        if ($visibility !== 'public') {
            $visibility = 'private';
        }

        try {

            $visibility = $this->_getDisk()->setVisibility($path, $visibility);

            if (!$visibility) {

                throw new Exceptions\FileMetadataException('Unable to set visibility');

            }

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Returns known metadata of a given file or directory
     *
     * NOTE: Returned array keys may differ depending on the adapter that is used
     *
     * @param string $path
     *
     * @return array
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function getMetadata(string $path): array
    {

        try {

            $meta = $this->_getDisk()->getMetadata($path);

            if ($meta) {

                return $meta;

            }

            throw new Exceptions\FileMetadataException('Unable to get metadata');

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Returns MIME type of given file or directory
     *
     * @param string $path
     *
     * @return string
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function getMimeType(string $path): string
    {

        try {

            $mime = $this->_getDisk()->getMimetype($path);

            if ($mime) {

                return $mime;

            }

            throw new Exceptions\FileMetadataException('Unable to get MIME type');

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Returns size in bytes of given file
     *
     * @param string $file
     *
     * @return int
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function getSize(string $file): int
    {

        try {

            $size = $this->_getDisk()->getSize($file);

            if ($size) {

                return $size;

            }

            throw new Exceptions\FileMetadataException('Unable to get size');

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Returns timestamp of given file or directory
     *
     * @param string $path
     *
     * @return int
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function getTimestamp(string $path): int
    {

        try {

            $time = $this->_getDisk()->getTimestamp($path);

            if ($time) {

                return $time;

            }

            throw new Exceptions\FileMetadataException('Unable to get timestamp');

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Touch file
     *
     * Technically, the actions being performed in this method are not directly working with
     * metadata, but because the thought of "touching" a file is simply to update the timestamp,
     * this method throws a FileMetadataException
     *
     * @param string $file
     *
     * @return void
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function touch(string $file): void
    {

        try {

            $disk = $this->_getDisk();

            if ($disk->copy($file, $file . '.tmp')) {

                if ($disk->delete($file)) {

                    if ($disk->rename($file . '.tmp', $file)) {

                        return;

                    }

                }

            }

            throw new Exceptions\FileMetadataException('Unable to touch');

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

    /**
     * Returns URL of a given path based on the "url" array key in the disk's configuration array
     *
     * @param string $path
     *
     * @return string
     *
     * @throws Exceptions\FileMetadataException
     *
     */

    public function url(string $path): string
    {

        try {

            $config = $this->config[$this->current_disk];

            $disk = $this->_getDisk();

            if (isset($config['url']) && $disk->has($path) && $disk->getVisibility($path) == 'public') {

                return filter_var($config['url'] . '/' . $path, FILTER_SANITIZE_URL);

            }

            throw new Exceptions\FileMetadataException('Unable to retrieve URL');

        } catch (Exception $e) {

            throw new Exceptions\FileMetadataException($e->getMessage());

        }

    }

}