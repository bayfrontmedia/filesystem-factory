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

namespace Bayfront\Filesystem\Adapters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Filesystem\AdapterInterface;
use Bayfront\Filesystem\Exceptions\ConfigurationException;
use League\Flysystem\Memory\MemoryAdapter as Adapter;

/*
 * Memory adapter
 *
 * See: https://flysystem.thephpleague.com/v1/docs/adapter/memory/
 */

class Memory implements AdapterInterface
{

    private static $required_config_keys = [ // Required config keys in "dot" notation
    ];

    /**
     * Create Adapter object
     *
     * @param array $config
     *
     * @return Adapter
     *
     * @throws ConfigurationException
     *
     */

    public static function create(array $config): Adapter
    {

        if (Arr::isMissing(Arr::dot($config), self::$required_config_keys)) {

            throw new ConfigurationException('Invalid storage adapter configuration');
        }

        return new Adapter();

    }

}