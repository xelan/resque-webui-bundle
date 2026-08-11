<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Double;

/**
 * Minimal stand-in for the Redis client returned by RedisAdapter::instance().
 *
 * Only the handful of commands the factories use is implemented. Values are
 * returned as strings throughout, the way a real Redis round trip does.
 */
class FakeRedisClient
{
    /**
     * @var string[]
     */
    private $keys;

    /**
     * @var array[]
     */
    private $hashes;

    /**
     * @var string[]
     */
    private $sets;

    /**
     * @param string[] $keys   keys reported by keys()
     * @param array[]  $hashes hash contents indexed by key
     * @param string[] $sets   set members indexed by key
     */
    public function __construct(array $keys = [], array $hashes = [], array $sets = [])
    {
        $this->keys = $keys;
        $this->hashes = $hashes;
        $this->sets = $sets;
    }

    public function keys($pattern)
    {
        return $this->keys;
    }

    public function hgetall($key)
    {
        return isset($this->hashes[$key]) ? $this->hashes[$key] : [];
    }

    public function smembers($key)
    {
        return isset($this->sets[$key]) ? $this->sets[$key] : [];
    }
}
