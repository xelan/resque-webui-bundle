<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Dto\Worker;

class WorkerTest extends TestCase
{
    public function testItExposesEveryConstructorArgument()
    {
        $worker = new Worker(
            'host:1:default',
            2,
            1500000000,
            'abc123',
            1500000001,
            10,
            2,
            1,
            5,
            60,
            1048576,
            128
        );

        $this->assertSame('host:1:default', $worker->getId());
        $this->assertSame(2, $worker->getStatus());
        $this->assertSame(1500000000, $worker->getStarted());
        $this->assertSame('abc123', $worker->getCurrentJobId());
        $this->assertSame(1500000001, $worker->getCurrentJobStarted());
        $this->assertSame(10, $worker->getJobsProcessed());
        $this->assertSame(2, $worker->getJobsCancelled());
        $this->assertSame(1, $worker->getJobsFailed());
        $this->assertSame(5, $worker->getInterval());
        $this->assertSame(60, $worker->getTimeout());
        $this->assertSame(1048576, $worker->getMemory());
        $this->assertSame(128, $worker->getMemoryLimit());
    }

    /**
     * WorkerFactory passes null when the worker is not processing a job.
     */
    public function testAnIdleWorkerHasNoCurrentJob()
    {
        $worker = new Worker('host:1:default', 2, 1500000000, null, 0, 0, 0, 0, 5, 60, 1048576, 128);

        $this->assertNull($worker->getCurrentJobId());
    }
}
