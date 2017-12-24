<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Adapter;

use Resque\Redis;

/**
 * Adapter class which provides the static methods of Resque\Redis as non-static methods.
 */
class RedisAdapter
{
    /**
     * Establishes a Redis connection
     *
     * @return Redis
     */
    public static function instance()
    {
        return Redis::instance();
    }

    /**
     * Set the Redis config.
     *
     * @param  array $config Array of configuration settings
     */
    public function setConfig(array $config)
    {
        return Redis::setConfig($config);
    }
}
