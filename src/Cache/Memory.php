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

namespace Bayfront\Filesystem\Cache;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Filesystem\CacheInterface;
use Bayfront\Filesystem\Exceptions\ConfigurationException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory as CacheStore;

/*
 * Memory caching
 *
 * See: https://flysystem.thephpleague.com/v1/docs/advanced/caching/
 */

class Memory implements CacheInterface
{

    private static $required_config_keys = [ // Required config keys in "dot" notation

    ];

    /**
     * Create cache object
     *
     * @param array $config
     * @param AdapterInterface $adapter
     *
     * @return CachedAdapter
     *
     * @throws ConfigurationException
     *
     */

    public static function create(array $config, AdapterInterface $adapter): CachedAdapter
    {

        if (Arr::isMissing(Arr::dot($config), self::$required_config_keys)) {

            throw new ConfigurationException('Invalid cache configuration');

        }

        return new CachedAdapter($adapter, new CacheStore());

    }

}