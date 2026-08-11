<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Adapter;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Adapter\QueueAdapter;

class QueueAdapterTest extends TestCase
{
    /**
     * @var QueueAdapter
     */
    private $adapter;

    protected function setUp(): void
    {
        $this->adapter = new QueueAdapter();
    }

    public function testRedisKeyWithoutAQueueAddressesTheQueueCollection()
    {
        $this->assertSame('queues', $this->adapter->redisKey());
    }

    /**
     * The docblock of the adapter announces a Queue object, but both the caller
     * (QueueFactory) and php-resque itself work on the plain queue name.
     */
    public function testRedisKeyIsBuiltFromTheQueueName()
    {
        $this->assertSame('queue:emails', $this->adapter->redisKey('emails'));
    }

    public function testRedisKeyAppendsTheSuffix()
    {
        $this->assertSame('queue:emails:stats', $this->adapter->redisKey('emails', 'stats'));
    }

    public function testRedisKeyDoesNotPrefixAnAlreadyPrefixedName()
    {
        $this->assertSame('queue:emails', $this->adapter->redisKey('queue:emails'));
    }
}
