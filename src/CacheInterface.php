<?php
/**
 * An easy to use filesystem factory built atop Flysystem v1.
 *
 * @version     1.0.0
 * @link        https://github.com/bayfrontmedia/filesystem-factory
 * @license     MIT https://opensource.org/licenses/MIT
 * @copyright   2020 Bayfront Media https://www.bayfrontmedia.com
 * @author      John Robinson <john@bayfrontmedia.com>
 */

namespace Bayfront\Filesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;

interface CacheInterface
{
    public static function create(array $config, AdapterInterface $adapter): CachedAdapter;
}