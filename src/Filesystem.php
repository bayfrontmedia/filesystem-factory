<?php

/**
 * @package filesystem-factory
 * @link https://github.com/bayfrontmedia/filesystem-factory
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\Filesystem;

use Bayfront\Filesystem\Exceptions\ConfigurationException;
use Bayfront\Filesystem\Exceptions\DirectoryCreateException;
use Bayfront\Filesystem\Exceptions\DirectoryDeleteException;
use Bayfront\Filesystem\Exceptions\DiskException;
use Bayfront\Filesystem\Exceptions\FileCopyException;
use Bayfront\Filesystem\Exceptions\FileDeleteException;
use Bayfront\Filesystem\Exceptions\FileMetadataException;
use Bayfront\Filesystem\Exceptions\FileMoveException;
use Bayfront\Filesystem\Exceptions\FileReadException;
use Bayfront\Filesystem\Exceptions\FileRenameException;
use Bayfront\Filesystem\Exceptions\FileWriteException;
use Exception;
use Bayfront\ArrayHelpers\Arr;
use League\Flysystem\Filesystem as Flysystem;

class Filesystem
{

    private $default_disk_name = 'default';

    private $adapters_namespace = 'Bayfront\\Filesystem\\Adapters\\';

    private $cache_namespace = 'Bayfront\\Filesystem\\Cache\\';

    private $config; // Filesystem config array

    private $current_disk; // Current disk name

    /*
     * Revert back to default disk after _getDisk() is called
     *
     * This is updated via the $make_default parameter of disk()
     */

    private $revert_disk_after_use = true;

    /**
     * Constructor.
     *
     * @param array $config (Filesystem configuration array)
     *
     * @return void
     *
     * @throws ConfigurationException
     *
     */

    public function __construct(array $config)
    {

        if (!isset($config[$this->default_disk_name])) { // Must have a "default" disk on the array

            throw new ConfigurationException('Invalid filesystem configuration');

        }

        $this->config = $config;

        $this->current_disk = $this->default_disk_name;

        $this->disk($this->default_disk_name); // Create instance

    }

    private $filesystems = []; // Active filesystem instances

    /**
     * Returns current disk's filesystem object, then resets current disk to default.
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
     * Returns textual representation of visibility from boolean value.
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
     * Returns the Flysystem instance for a given disk name.
     *
     * @param string $name
     *
     * @return Flysystem
     *
     * @throws DiskException
     */

    public function getDisk(string $name): Flysystem
    {

        $current = $this->current_disk;

        /*
         * Force the creation of this disk if it does not already exist
         */

        $this->disk($name)->disk($current);

        if (!isset($this->filesystems[$name])) {
            throw new DiskException('Disk does not exist (' . $name . ')');
        }

        return $this->filesystems[$name];

    }

    /**
     * Returns the Flysystem instance for the default disk.
     *
     * @return Flysystem
     */

    public function getDefaultDisk(): Flysystem
    {
        return $this->getDisk($this->getDefaultDiskName());
    }

    /**
     * Returns the Flysystem instance for the current disk.
     *
     * @return Flysystem
     */

    public function getCurrentDisk(): Flysystem
    {
        return $this->getDisk($this->getCurrentDiskName());
    }

    /**
     * Returns array of disk names which have been created.
     *
     * @return array
     */

    public function getDiskNames(): array
    {
        return array_keys($this->filesystems);
    }

    /**
     * Returns name of the default disk.
     *
     * @return string
     */

    public function getDefaultDiskName(): string
    {
        return $this->default_disk_name;
    }

    /**
     * Returns name of the current disk.
     *
     * @return string
     */

    public function getCurrentDiskName(): string
    {
        return $this->current_disk;
    }

    /**
     * Sets the current disk, creating a new filesystem instance if not already existing.
     *
     * @param string $name
     * @param bool $make_default
     *
     * @return self
     *
     * @throws ConfigurationException
     * @throws DiskException
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

            throw new ConfigurationException('Invalid disk configuration');

        }

        if (!class_exists($this->adapters_namespace . $this->config[$name]['adapter'])) {

            throw new DiskException('Disk adapter does not exist (' . $this->config[$name]['adapter'] . ')');

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

            throw new DiskException($e->getMessage(), 0, $e);

        }

    }

    /*
     * ############################################################
     * Files and directories
     * ############################################################
     */

    /**
     * Write/overwrite file.
     *
     * NOTE: This method uses Flysystem's put method.
     *
     * @param string $file
     * @param string $contents
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws FileWriteException
     *
     */

    public function write(string $file, string $contents, bool $public = false): void
    {

        try {

            $write = $this->_getDisk()->put($file, $contents, ['visibility' => $this->_boolToVisibility($public)]);

            if ($write) {

                return;

            }

            throw new FileWriteException('Unable to write (' . $file . ')');

        } catch (Exception $e) {

            throw new FileWriteException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Write/overwrite file using a stream.
     *
     * NOTE: This method uses Flysystem's putStream method.
     *
     * @param string $file
     * @param resource $resource
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws FileWriteException
     *
     */

    public function writeStream(string $file, $resource, bool $public = false): void
    {

        try {

            $write = $this->_getDisk()->putStream($file, $resource, ['visibility' => $this->_boolToVisibility($public)]);

            if ($write) {

                return;

            }

            throw new FileWriteException('Unable to write stream (' . $file . ')');

        } catch (Exception $e) {

            throw new FileWriteException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Prepends existing file with given contents.
     *
     * @param string $file
     * @param string $contents
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws FileWriteException
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

            throw new FileWriteException('Unable to prepend (' . $file . ')');

        } catch (Exception $e) {

            throw new FileWriteException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Appends existing file with given contents.
     *
     * @param string $file
     * @param string $contents
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws FileWriteException
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

            throw new FileWriteException('Unable to append (' . $file . ')');

        } catch (Exception $e) {

            throw new FileWriteException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Checks if a given file or directory exists.
     *
     * NOTE: Behavior may be inconsistent with directories, depending on the adapter being used.
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
     * Checks if a given file or directory does not exist.
     *
     * NOTE: Behavior may be inconsistent with directories, depending on the adapter being used.
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
     * Renames a file or folder.
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     *
     * @throws FileRenameException
     *
     */

    public function rename(string $from, string $to): void
    {

        try {

            $rename = $this->_getDisk()->rename($from, $to);

            if ($rename) {

                return;

            }

            throw new FileRenameException('Unable to rename');

        } catch (Exception $e) {

            throw new FileRenameException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Copies file from one location to another.
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     *
     * @throws FileCopyException
     *
     */

    public function copy(string $from, string $to): void
    {

        try {

            $copy = $this->_getDisk()->copy($from, $to);

            if ($copy) {

                return;

            }

            throw new FileCopyException('Unable to copy');

        } catch (Exception $e) {

            throw new FileCopyException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Moves file from one location to another.
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     *
     * @throws FileMoveException
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

            throw new FileMoveException('Unable to move');

        } catch (Exception $e) {

            throw new FileMoveException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns the contents of a file.
     *
     * @param string $file
     *
     * @return string
     *
     * @throws FileReadException
     *
     */

    public function read(string $file): string
    {

        try {

            $get = $this->_getDisk()->read($file);

            if ($get) {

                return $get;

            }

            throw new FileReadException('Unable to read (' . $file . ')');

        } catch (Exception $e) {

            throw new FileReadException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns resource.
     *
     * @param string $file
     *
     * @return resource
     *
     * @throws FileReadException
     *
     */

    public function readStream(string $file)
    {

        try {

            $get = $this->_getDisk()->readStream($file);

            if ($get) {

                return $get;

            }

            throw new FileReadException('Unable to read stream (' . $file . ')');

        } catch (Exception $e) {

            throw new FileReadException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns the contents of file, then deletes it.
     *
     * @param string $file
     *
     * @return string
     *
     * @throws FileReadException
     *
     */

    public function readAndDelete(string $file): string
    {

        try {

            $get = $this->_getDisk()->readAndDelete($file);

            if ($get) {

                return $get;

            }

            throw new FileReadException('Unable to read and delete (' . $file . ')');

        } catch (Exception $e) {

            throw new FileReadException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Deletes a file.
     *
     * @param string $file
     *
     * @return void
     *
     * @throws FileDeleteException
     *
     */

    public function delete(string $file): void
    {

        try {

            $delete = $this->_getDisk()->delete($file);

            if (!$delete) {

                throw new FileDeleteException('Unable to delete (' . $file . ')');

            }

        } catch (Exception $e) {

            throw new FileDeleteException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param bool $public (Visibility)
     *
     * @return void
     *
     * @throws DirectoryCreateException
     *
     */

    public function createDir(string $path, bool $public = false): void
    {

        try {

            $dir = $this->_getDisk()->createDir($path, ['visibility' => $this->_boolToVisibility($public)]);

            if ($dir) {

                return;

            }

            throw new DirectoryCreateException('Unable to create directory (' . $path . ')');

        } catch (Exception $e) {

            throw new DirectoryCreateException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Delete a directory.
     *
     * @param string $path
     *
     * @return void
     *
     * @throws DirectoryDeleteException
     *
     */

    public function deleteDir(string $path): void
    {

        try {

            $dir = $this->_getDisk()->deleteDir($path);

            if ($dir) {

                return;

            }

            throw new DirectoryDeleteException('Unable to delete directory (' . $path . ')');

        } catch (Exception $e) {

            throw new DirectoryDeleteException($e->getMessage(), 0, $e);

        }

    }

    /**
     * List all contents of a directory.
     *
     * NOTE: Returned array keys may differ depending on the adapter used.
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
     * List all files in a directory.
     *
     * NOTE: Returned array keys may differ depending on the adapter used.
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
     * List all files in a directory except files with given extension(s).
     *
     * NOTE: Returned array keys may differ depending on the adapter used.
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
     * List all directories in a path.
     *
     * NOTE: Returned array keys may differ depending on the adapter used.
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
     * Returns the visibility of a given file or directory.
     *
     * @param string $path
     *
     * @return string ("public" or "private")
     *
     * @throws FileMetadataException
     *
     */

    public function getVisibility(string $path): string
    {

        try {

            return $this->_getDisk()->getVisibility($path);

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Checks if the visibility of a given file or directory is "public".
     *
     * @param string $path
     *
     * @return bool
     *
     * @throws FileMetadataException
     *
     */

    public function isPublic(string $path): bool
    {

        try {

            return $this->_getDisk()->getVisibility($path) == 'public';

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Checks if the visibility of a given file or directory is "private".
     *
     * @param string $path
     *
     * @return bool
     *
     * @throws FileMetadataException
     *
     */

    public function isPrivate(string $path): bool
    {

        try {

            return $this->_getDisk()->getVisibility($path) == 'private';

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Sets the visibility of a given file or directory to "public".
     *
     * @param string $path
     *
     * @return void
     *
     * @throws FileMetadataException
     *
     */

    public function setPublic(string $path): void
    {

        try {

            $visibility = $this->_getDisk()->setVisibility($path, 'public');

            if (!$visibility) {

                throw new FileMetadataException('Unable to set visibility as public (' . $path . ')');

            }

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Sets the visibility of a given file or directory to "private".
     *
     * @param string $path
     *
     * @return void
     *
     * @throws FileMetadataException
     *
     */

    public function setPrivate(string $path): void
    {

        try {

            $visibility = $this->_getDisk()->setVisibility($path, 'private');

            if (!$visibility) {

                throw new FileMetadataException('Unable to set visibility as private (' . $path . ')');

            }

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Sets the visibility of a given file or directory.
     *
     * @param string $path
     * @param string $visibility ("public" or "private")
     *
     * @return void
     *
     * @throws FileMetadataException
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

                throw new FileMetadataException('Unable to set visibility (' . $path . ')');

            }

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns known metadata of a given file or directory.
     *
     * NOTE: Returned array keys may differ depending on the adapter that is used.
     *
     * @param string $path
     *
     * @return array
     *
     * @throws FileMetadataException
     *
     */

    public function getMetadata(string $path): array
    {

        try {

            $meta = $this->_getDisk()->getMetadata($path);

            if ($meta) {

                return $meta;

            }

            throw new FileMetadataException('Unable to get metadata (' . $path . ')');

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns MIME type of given file or directory.
     *
     * @param string $path
     *
     * @return string
     *
     * @throws FileMetadataException
     *
     */

    public function getMimeType(string $path): string
    {

        try {

            $mime = $this->_getDisk()->getMimetype($path);

            if ($mime) {

                return $mime;

            }

            throw new FileMetadataException('Unable to get MIME type (' . $path . ')');

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns size in bytes of given file.
     *
     * @param string $file
     *
     * @return int
     *
     * @throws FileMetadataException
     *
     */

    public function getSize(string $file): int
    {

        try {

            $size = $this->_getDisk()->getSize($file);

            if ($size) {

                return $size;

            }

            throw new FileMetadataException('Unable to get size (' . $file . ')');

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns timestamp of given file or directory.
     *
     * @param string $path
     *
     * @return int
     *
     * @throws FileMetadataException
     *
     */

    public function getTimestamp(string $path): int
    {

        try {

            $time = $this->_getDisk()->getTimestamp($path);

            if ($time) {

                return $time;

            }

            throw new FileMetadataException('Unable to get timestamp (' . $path . ')');

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Touch file.
     *
     * Technically, the actions being performed in this method are not directly working with
     * metadata, but because the thought of "touching" a file is simply to update the timestamp,
     * this method throws a FileMetadataException.
     *
     * @param string $file
     *
     * @return void
     *
     * @throws FileMetadataException
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

            throw new FileMetadataException('Unable to touch (' . $file . ')');

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

    /**
     * Returns URL of a given path based on the "url" array key in the disk's configuration array.
     *
     * @param string $path
     *
     * @return string
     *
     * @throws FileMetadataException
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

            throw new FileMetadataException('Unable to retrieve URL (' . $path . ')');

        } catch (Exception $e) {

            throw new FileMetadataException($e->getMessage(), 0, $e);

        }

    }

}