## Filesystem factory

An easy to use filesystem factory built atop [Flysystem v1](https://github.com/thephpleague/flysystem).

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](https://github.com/bayfrontmedia/php-array-helpers/blob/master/LICENSE).

## Author

John Robinson, [Bayfront Media](https://www.bayfrontmedia.com)

## Requirements

* PHP > 7.1.0

## Installation

```
composer require bayfrontmedia/filesystem-factory
```

## Usage

### Configuration array

The configuration array allows you to set up all of your "disks". You can have as many disks as you like, and each disk uses its own adapter and has its own settings.

Each disk must have a unique name. The `default` disk is required, and will always be used unless another disk is specified using `disk()`.

To utilize the `url()` method (optional), add a `url` key which points to the root path of the disk.

To enable caching for a disk (optional), add a `cache` array with a `location` key. Additional keys may be optional or required depending on the type of caching being used.

**Example:**

```
use Bayfront\Filesystem\Filesystem;

$config = [
    'default' => [ // Name of disk ("default" required)
        'adapter' => 'Local', // Class name in Bayfront\Filesystem\Adapters namespace
        'root' => 'path/to/root',
        'permissions' => [
            'file' => [
                'public' => 0644,
                'private' => 0600
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ]
        ],
        'url' => 'http://localhost/path/to/root', // Optional key
        'cache' => [ // Optional key
            'location' => 'Memory' // Class name in Bayfront\Filesystem\Cache namespace
        ]
    ]
]

$storage = new Filesystem($config);
```

### Adapters

Each adapter has its own required configuration array keys, as shown below.

**Digital Ocean**

Installation: `composer require league/flysystem-aws-s3-v3`

```
[
    'adapter' => 'DigitalOcean',
    'credentials' => [
        'key' => 'YOUR_KEY',
        'secret' => 'YOUR_SECRET',
    ],
    'region' => 'SPACES_REGION',
    'version' => 'latest',
    'endpoint' => 'https://DATACENTER.digitaloceanspaces.com',
    'bucket' => 'BUCKET_NAME'
]
```

**FTP**

```
[
    'adapter' => 'Ftp',
    'host' => 'FTP_HOST',
    'username' => 'USERNAME',
    'password' => 'PASSWORD',
    'root' => 'path/to/root',
    
    /* The following are optional */

    'port' => 21,
    'passive' => true,
    'ssl' => true,
    'timeout' => 30,
    'ignorePassiveAddress' => false
]
```

**Local**

```
[
    'adapter' => 'Local',
    'root' => 'path/to/root',
    'permissions' => [
        'file' => [
            'public' => 0644,
            'private' => 0600
        ],
        'dir' => [
            'public' => 0755,
            'private' => 0700,
        ]
    ]
]
```

**Memory**

Installation: `composer require league/flysystem-memory`

```
[
    'adapter' => 'Memory'
]
```

**SFTP**

Installation: `composer require league/flysystem-sftp`

```
[
    'adapter' => 'Sftp',
    'host' => 'FTP_HOST',
    'username' => 'USERNAME',
    'password' => 'PASSWORD',
    'root' => 'path/to/root',
    
    /* The following are optional */

    'port' => 21,
    'privateKey' => 'path/to/or/contents/of/privatekey',
    'passphrase' => 'passphrase-for-privateKey',
    'timeout' => 10,
    'directoryPerm' => 0755
]
```

### Caching

**Memory**

This type of caching will cache everything in the lifetime of the current process (cli-job or http-request).

Installation: `league/flysystem-cached-adapter`

```
[
    'location' => 'Memory'
]
```

### Public methods

**Files and directories**

- [disk](#disk)
- [getCurrentDisk](#getcurrentdisk)
- [write](#write)
- [writeStream](#writestream)
- [prepend](#prepend)
- [append](#append)
- [exists](#exists)
- [missing](#missing)
- [rename](#rename)
- [copy](#copy)
- [move](#move)
- [read](#read)
- [readStream](#readstream)
- [readAndDelete](#readanddelete)
- [delete](#delete)
- [createDir](#createdir)
- [deleteDir](#deletedir)
- [listContents](#listcontents)
- [listFiles](#listfiles)
- [listFilesExcept](#listfilesexcept)
- [listDirs](#listdirs)

**Metadata**

- [getVisibility](#getvisibility)
- [isPublic](#ispublic)
- [isPrivate](#isprivate)
- [setPublic](#setpublic)
- [setPrivate](#setprivate)
- [setVisibility](#setvisibility)
- [getMetadata](#getmetadata)
- [getMimeType](#getmimetype)
- [getSize](#getsize)
- [getTimestamp](#gettimestamp)
- [touch](#touch)
- [url](#url)

<hr />

### disk

**Description:**

Sets the current disk, creating a new filesystem object if not already existing.

**Parameters:**

- `$name` (string)
- `$make_default = false` (bool)

**Returns:**

- (self)

**Throws:**

- `Bayfront\Filesystem\Exceptions\ConfigurationException`
- `Bayfront\Filesystem\Exceptions\DiskException`

**Example:**

```
try {

    $filesystem->disk('cdn')->write('file.txt', 'This is the content.');

} catch (FileWriteException $e) {

    echo $e->getMessage();

}
```

<hr />

### getCurrentDisk

**Description:**

Returns name of current disk.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```
echo $filesystem->getCurrentDisk();
```

<hr />

### write

**Description:**

Write/overwrite file.

**Parameters:**

- `$file` (string)
- `$contents` (string)
- `$public = false` (bool): Visibility

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileWriteException`

**Example:**

```
try {

    $filesystem->write('file.txt', 'This is the content.');

} catch (FileWriteException $e) {

    echo $e->getMessage();

}
```

<hr />

### writeStream

**Description:**

Write/overwrite file using a stream.

**Parameters:**

- `$file` (string)
- `$resource` (resource)
- `$public = false` (bool): Visibility

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileWriteException`

<hr />

### prepend

**Description:**

Prepends existing file with given contents.

**Parameters:**

- `$file` (string)
- `$contents` (string)
- `$public = false` (bool): Visibility

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileWriteException`

**Example:**

```
try {

    $filesystem->prepend('file.txt', 'Prepended content. ');

} catch (FileWriteException $e) {

    echo $e->getMessage();

}
```

<hr />

### append

**Description:**

Appends existing file with given contents.

**Parameters:**

- `$file` (string)
- `$contents` (string)
- `$public = false` (bool): Visibility

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileWriteException`

**Example:**

```
try {

    $filesystem->append('file.txt', ' Appended content.');

} catch (FileWriteException $e) {

    echo $e->getMessage();

}
```

<hr />

### exists

**Description:**

Checks if a given file or directory exists.

**NOTE:** Behavior may be inconsistent with directories, depending on the adapter being used.

**Parameters:**

- `$path` (string)

**Returns:**

- (bool)

**Example:**

```
if ($filesystem->exists('file.txt')) {
    // Do something
}
```

<hr />

### missing

**Description:**

Checks if a given file or directory does not exist.

**NOTE:** Behavior may be inconsistent with directories, depending on the adapter being used.

**Parameters:**

- `$path` (string)

**Returns:**

- (bool)

**Example:**

```
if ($filesystem->missing('file.txt')) {
    // Do something
}
```

<hr />

### rename

**Description:**

Renames a file or folder.

**Parameters:**

- `$from` (string)
- `$to` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileRenameException`

**Example:**

```
try {

    $filesystem->rename('file.txt', 'newfile.txt');

} catch (FileRenameException $e) {

    echo $e->getMessage();

}
```

<hr />

### copy

**Description:**

Copies file from one location to another.

**Parameters:**

- `$from` (string)
- `$to` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileCopyException`

**Example:**

```
try {

    $filesystem->copy('file.txt', 'copiedfile.txt');

} catch (FileCopyException $e) {

    echo $e->getMessage();

}
```

<hr />

### move

**Description:**

Moves file from one location to another.

**Parameters:**

- `$from` (string)
- `$to` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMoveExeption`

**Example:**

```
try {

    $filesystem->move('file.txt', 'subfolder/file.txt');

} catch (FileMoveException $e) {

    echo $e->getMessage();

}
```

<hr />

### read

**Description:**

Returns the contents of a file.

**Parameters:**

- `$file` (string)

**Returns:**

- (string)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileReadException`

**Example:**

```
try {

    echo $filesystem->read('file.txt');

} catch (FileReadException $e) {

    echo $e->getMessage();

}
```

<hr />

### readStream

**Description:**

Returns resource.

**Parameters:**

- `$file` (string)

**Returns:**

- (resource)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileReadException`

<hr />

### readAndDelete

**Description:**

Returns the contents of file, then deletes it.

**Parameters:**

- `$file` (string)

**Returns:**

- (string)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileReadException`

**Example:**

```
try {

    echo $filesystem->readAndDelete('file.txt');

} catch (FileReadException $e) {

    echo $e->getMessage();

}
```

<hr />

### delete

**Description:**

Deletes a file.

**Parameters:**

- `$file` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileDeleteException`

**Example:**

```
try {

    $filesystem->delete('file.txt');

} catch (FileDeleteException $e) {

    echo $e->getMessage();

}
```

<hr />

### createDir

**Description:**

Create a directory.

**Parameters:**

- `$path` (string)
- `$public = false` (bool)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\DirectoryCreateException`

**Example:**

```
try {

    $filesystem->createDir('subfolder');

} catch (DirectoryCreateException $e) {

    echo $e->getMessage();

}
```

<hr />

### deleteDir

**Description:**

Delete a directory.

**Parameters:**

- `$path` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\DirectoryDeleteException`

**Example:**

```
try {

    $filesystem->deleteDir('subfolder');

} catch (DirectoryDeleteException $e) {

    echo $e->getMessage();

}
```

<hr />

### listContents

**Description:**

List all contents of a directory.

**NOTE:** Returned array keys may differ depending on the adapter used.

**Parameters:**

- `$path` (string)
- `$recursive = false` (bool)

**Returns:**

- (array)

**Example:**

```
$contents = $filesystem->listContents('/');
```

<hr />

### listFiles

**Description:**

List all files in a directory.

**NOTE:** Returned array keys may differ depending on the adapter used.

**Parameters:**

- `$path` (string)
- `$recursive = false` (bool)
- `$extensions = []` (string|array): List only files with given extension(s)

**Returns:**

- (array)

**Example:**

```
$files = $filesystem->listFiles('/');
```

<hr />

### listFilesExcept

**Description:**

List all files in a directory except files with given extension(s).

**NOTE:** Returned array keys may differ depending on the adapter used.

**Parameters:**

- `$path` (string)
- `$recursive = false` (bool)
- `$extensions = []` (string|array): Extension(s) to exclude

**Returns:**

- (array)

**Example:**

```
$files = $filesystem->listFilesExcept('/', false, [
    'jpg',
    'jpeg'
]);
```

<hr />

### listDirs

**Description:**

List all directories in a path.

**NOTE:** Returned array keys may differ depending on the adapter used.

**Parameters:**

- `$path` (string)
- `$recursive = false` (bool)

**Returns:**

- (array)

**Example:**

```
$dirs = $filesystem->listDirs('/');
```

<hr />

### getVisibility

**Description:**

Returns the visibility of a given file or directory.

**Parameters:**

- `$path` (string)

**Returns:**

- (string): "public" or "private"

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    echo $filesystem->getVisibility('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### isPublic

**Description:**

Checks if the visibility of a given file or directory is "public".

**Parameters:**

- `$path` (string)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    if ($filesystem->isPublic('file.txt')) {
        // Do something
    }

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### isPrivate

**Description:**

Checks if the visibility of a given file or directory is "private".

**Parameters:**

- `$path` (string)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    if ($filesystem->isPrivate('file.txt')) {
        // Do something
    }

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### setPublic

**Description:**

Sets the visibility of a given file or directory to "public".

**Parameters:**

- `$path` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    $filesystem->setPublic('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### setPrivate

**Description:**

Sets the visibility of a given file or directory to "private".

**Parameters:**

- `$path` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    $filesystem->setPrivate('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### setVisibility

**Description:**

Sets the visibility of a given file or directory.

**Parameters:**

- `$path` (string)
- `$visibility` (string): "public" or "private"

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    $filesystem->setVisibility('file.txt', 'public');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### getMetadata

**Description:**

Returns known metadata of a given file or directory.

**NOTE:** Returned array keys may differ depending on the adapter this is used.

**Parameters:**

- `$path` (string)

**Returns:**

- (array)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    $metadata = $filesystem->getMetadata('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### getMimeType

**Description:**

Returns MIME type of given file or directory.

**Parameters:**

- `$path` (string)

**Returns:**

- (string)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    echo $filesystem->getMimeType('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### getSize

**Description:**

Returns size in bytes of given file.

**Parameters:**

- `$file` (string)

**Returns:**

- (int)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    echo $filesystem->getSize('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### getTimestamp

**Description:**

Returns timestamp of given file or directory.

**Parameters:**

- `$path` (string)

**Returns:**

- (int)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    echo $filesystem->getTimestamp('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### touch

**Description:**

Touch file.

**Parameters:**

- `$file` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    $filesystem->touch('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```

<hr />

### url

**Description:**

Returns URL of a given file or directory based on the "url" array key in the disk's configuration array. URL will only be returned if the path exists and visibility is "public".

**Parameters:**

- `$path` (string)

**Returns:**

- (string)

**Throws:**

- `Bayfront\Filesystem\Exceptions\FileMetadataException`

**Example:**

```
try {

    echo $filesystem->url('file.txt');

} catch (FileMetadataException $e) {

    echo $e->getMessage();

}
```