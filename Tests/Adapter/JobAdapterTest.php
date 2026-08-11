<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Resque\Job;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;

use InvalidArgumentException;

class JobAdapterTest extends TestCase
{
    /**
     * @var JobAdapter
     */
    private $adapter;

    protected function setUp(): void
    {
        $this->adapter = new JobAdapter();
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
            'waiting' => [Job::STATUS_WAITING, 'Waiting'],
            'delayed' => [Job::STATUS_DELAYED, 'Delayed'],
            'running' => [Job::STATUS_RUNNING, 'Running'],
            'complete' => [Job::STATUS_COMPLETE, 'Complete'],
            'cancelled' => [Job::STATUS_CANCELLED, 'Cancelled'],
            'failed' => [Job::STATUS_FAILED, 'Failed'],
        ];
    }

    /**
     * Redis hands the status back as a string, so the numeric string has to map
     * onto the same label as the integer constant.
     */
    public function testGetStatusTextAcceptsTheNumericStringsComingFromRedis()
    {
        $this->assertSame('Running', $this->adapter->getStatusText((string) Job::STATUS_RUNNING));
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
            'one above the highest' => [7, 'Invalid status "7"!'],
            'far out of range' => [99, 'Invalid status "99"!'],
            'empty string' => ['', 'Invalid status ""!'],
            'non numeric' => ['bogus', 'Invalid status "bogus"!'],
        ];
    }

    public function testRedisKeyIsBuiltFromTheJobId()
    {
        $this->assertSame('job:abc123', JobAdapter::redisKey('abc123'));
        $this->assertSame('job:abc123:suffix', JobAdapter::redisKey('abc123', 'suffix'));
    }
}
