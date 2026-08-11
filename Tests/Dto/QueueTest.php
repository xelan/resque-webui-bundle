<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Dto\Queue;

class QueueTest extends TestCase
{
    public function testItExposesEveryConstructorArgument()
    {
        $queue = new Queue('emails', 1, 2, 3, 4, 5);

        $this->assertSame('emails', $queue->getName());
        $this->assertSame(1, $queue->getJobsQueued());
        $this->assertSame(2, $queue->getJobsDelayed());
        $this->assertSame(3, $queue->getJobsProcessed());
        $this->assertSame(4, $queue->getJobsCancelled());
        $this->assertSame(5, $queue->getJobsFailed());
    }

    public function testTheTotalIsTheSumOfAllCounters()
    {
        $queue = new Queue('emails', 1, 2, 3, 4, 5);

        $this->assertSame(15, $queue->getJobsTotal());
    }

    public function testTheTotalOfAnUntouchedQueueIsZero()
    {
        $queue = new Queue('emails', 0, 0, 0, 0, 0);

        $this->assertSame(0, $queue->getJobsTotal());
    }

    /**
     * QueueFactory casts the Redis values, but getJobsTotal() casts again and
     * therefore also adds up numeric strings.
     */
    public function testTheTotalAlsoAddsUpNumericStrings()
    {
        $queue = new Queue('emails', '1', '2', '3', '4', '5');

        $this->assertSame(15, $queue->getJobsTotal());
    }
}
