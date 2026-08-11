<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Double;

use Andaris\ResqueWebUiBundle\Adapter\RedisAdapter;

/**
 * RedisAdapter::instance() is static, so it cannot be stubbed by a PHPUnit mock.
 * A subclass can override it though, and because the factories call it on an
 * instance ($adapter->instance()) the override is picked up.
 */
class FakeRedisAdapter extends RedisAdapter
{
    /**
     * @var FakeRedisClient
     */
    private static $client;

    /**
     * @param FakeRedisClient $client
     *
     * @return FakeRedisAdapter
     */
    public static function withClient(FakeRedisClient $client)
    {
        self::$client = $client;

        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public static function instance()
    {
        return self::$client;
    }
}
