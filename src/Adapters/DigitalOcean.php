<?php

/**
 * @package filesystem-factory
 * @link https://github.com/bayfrontmedia/filesystem-factory
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\Filesystem\Adapters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Filesystem\AdapterInterface;
use Bayfront\Filesystem\Exceptions\ConfigurationException;
use Aws\S3\S3Client as Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter as Adapter;

/*
 * S3 adapter for DigitalOcean
 *
 * See: https://flysystem.thephpleague.com/v1/docs/adapter/digitalocean-spaces/
 */

class DigitalOcean implements AdapterInterface
{

    private static $required_config_keys = [ // Required config keys in "dot" notation
        'credentials.key',
        'credentials.secret',
        'region',
        'version',
        'endpoint',
        'bucket'
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

        $client = new Client(Arr::except($config, [
            'bucket',
            'driver'
        ]));

        return new Adapter($client, $config['bucket']);

    }

}