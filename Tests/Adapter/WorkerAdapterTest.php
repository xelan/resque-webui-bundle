<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Resque\Worker;

use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;

use InvalidArgumentException;

class WorkerAdapterTest extends TestCase
{
    /**
     * @var WorkerAdapter
     */
    private $adapter;

    protected function setUp(): void
    {
        $this->adapter = new WorkerAdapter();
    }

    /**
     * @dataProvider knownStatusProvider
     */
    public function testGetStatusTextReturnsTheLabelOfAKnownStatus($status, $expected)
    {
        $this->assertSame($expected, $this->adapter->getStatusText($status));
    }

    public function knownStatusProvider()
    {
        return [
            'new' => [Worker::STATUS_NEW, 'Not started'],
            'running' => [Worker::STATUS_RUNNING, 'Running'],
            'paused' => [Worker::STATUS_PAUSED, 'Paused'],
        ];
    }

    /**
     * Redis hands the status back as a string, so the numeric string has to map
     * onto the same label as the integer constant.
     */
    public function testGetStatusTextAcceptsTheNumericStringsComingFromRedis()
    {
        $this->assertSame('Running', $this->adapter->getStatusText((string) Worker::STATUS_RUNNING));
    }

    /**
     * @dataProvider unknownStatusProvider
     */
    public function testGetStatusTextRejectsAnUnknownStatus($status, $expectedMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->adapter->getStatusText($status);
    }

    public function unknownStatusProvider()
    {
        return [
            'one below the lowest' => [0, 'Invalid status "0"!'],
            'one above the highest' => [4, 'Invalid status "4"!'],
            'non numeric' => ['bogus', 'Invalid status "bogus"!'],
        ];
    }

    public function testRedisKeyWithoutAWorkerAddressesTheWorkerCollection()
    {
        $this->assertSame('workers', $this->adapter->redisKey());
    }

    public function testRedisKeyIsBuiltFromTheWorkerId()
    {
        $this->assertSame('worker:host:1:default', $this->adapter->redisKey('host:1:default'));
    }

    public function testRedisKeyAppendsTheSuffix()
    {
        $this->assertSame('worker:host:1:default:started', $this->adapter->redisKey('host:1:default', 'started'));
    }
}
