<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Adapter\QueueAdapter;
use Andaris\ResqueWebUiBundle\Dto\Queue;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisAdapter;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisClient;

class QueueFactoryTest extends TestCase
{
    public function testItReturnsAnEmptyListWithoutQueues()
    {
        $this->assertSame([], $this->createFactory(new FakeRedisClient())->createAll());
    }

    public function testItReadsTheStatsOfEveryQueue()
    {
        $client = new FakeRedisClient(
            [],
            ['queue:emails:stats' => [
                'queued' => '1',
                'delayed' => '2',
                'processed' => '3',
                'cancelled' => '4',
                'failed' => '5',
            ]],
            ['queues' => ['emails']]
        );

        $queues = $this->createFactory($client)->createAll();

        $this->assertCount(1, $queues);
        $this->assertInstanceOf(Queue::class, $queues[0]);
        $this->assertSame('emails', $queues[0]->getName());
        $this->assertSame(15, $queues[0]->getJobsTotal());
    }

    /**
     * The Redis values are strings and have to end up as integers on the DTO.
     */
    public function testItCastsTheCountersToIntegers()
    {
        $client = new FakeRedisClient(
            [],
            ['queue:emails:stats' => ['queued' => '7']],
            ['queues' => ['emails']]
        );

        $queue = $this->createFactory($client)->createAll()[0];

        $this->assertSame(7, $queue->getJobsQueued());
    }

    public function testAQueueWithoutStatsCountsZero()
    {
        $client = new FakeRedisClient([], [], ['queues' => ['emails']]);

        $queue = $this->createFactory($client)->createAll()[0];

        $this->assertSame(0, $queue->getJobsQueued());
        $this->assertSame(0, $queue->getJobsDelayed());
        $this->assertSame(0, $queue->getJobsProcessed());
        $this->assertSame(0, $queue->getJobsCancelled());
        $this->assertSame(0, $queue->getJobsFailed());
        $this->assertSame(0, $queue->getJobsTotal());
    }

    public function testItKeepsTheOrderOfTheQueueSet()
    {
        $client = new FakeRedisClient([], [], ['queues' => ['emails', 'reports']]);

        $queues = $this->createFactory($client)->createAll();

        $this->assertSame(['emails', 'reports'], [$queues[0]->getName(), $queues[1]->getName()]);
    }

    private function createFactory(FakeRedisClient $client)
    {
        return new QueueFactory(new QueueAdapter(), FakeRedisAdapter::withClient($client));
    }
}
