<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;
use Andaris\ResqueWebUiBundle\Dto\Worker;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeResqueWorker;

class WorkerFactoryTest extends TestCase
{
    public function testItReturnsAnEmptyListWhenNoWorkerIsKnown()
    {
        $factory = new WorkerFactory($this->createAdapter([]));

        $this->assertSame([], $factory->createAll());
    }

    public function testItMapsThePacketOntoTheDto()
    {
        $resqueWorker = $this->createResqueWorker('host:1:default', [
            'status' => 2,
            'started' => 1500000000,
            'job_id' => 'abc123',
            'job_started' => 1500000001,
            'processed' => 10,
            'cancelled' => 2,
            'failed' => 1,
            'interval' => 5,
            'timeout' => 60,
            'memory' => 1048576,
            'memory_limit' => 128,
        ]);

        $workers = (new WorkerFactory($this->createAdapter([$resqueWorker])))->createAll();

        $this->assertCount(1, $workers);
        $this->assertInstanceOf(Worker::class, $workers[0]);
        $this->assertSame('host:1:default', $workers[0]->getId());
        $this->assertSame(2, $workers[0]->getStatus());
        $this->assertSame(1500000000, $workers[0]->getStarted());
        $this->assertSame('abc123', $workers[0]->getCurrentJobId());
        $this->assertSame(10, $workers[0]->getJobsProcessed());
        $this->assertSame(128, $workers[0]->getMemoryLimit());
    }

    /**
     * @dataProvider emptyJobIdProvider
     */
    public function testAWorkerWithoutACurrentJobHasNoJobId($jobId)
    {
        $resqueWorker = $this->createResqueWorker('host:1:default', $this->createPacket(['job_id' => $jobId]));

        $workers = (new WorkerFactory($this->createAdapter([$resqueWorker])))->createAll();

        $this->assertNull($workers[0]->getCurrentJobId());
    }

    public function emptyJobIdProvider()
    {
        return [
            'missing' => [null],
            'empty string' => [''],
            'zero string' => ['0'],
        ];
    }

    public function testItKeepsTheOrderOfTheAdapter()
    {
        $adapter = $this->createAdapter([
            $this->createResqueWorker('host:1:default', $this->createPacket()),
            $this->createResqueWorker('host:2:default', $this->createPacket()),
        ]);

        $workers = (new WorkerFactory($adapter))->createAll();

        $this->assertSame(['host:1:default', 'host:2:default'], [$workers[0]->getId(), $workers[1]->getId()]);
    }

    private function createAdapter(array $workers)
    {
        $adapter = $this->createMock(WorkerAdapter::class);
        $adapter->method('allWorkers')->willReturn($workers);

        return $adapter;
    }

    private function createResqueWorker($id, array $packet)
    {
        return new FakeResqueWorker($id, $packet);
    }

    private function createPacket(array $overrides = [])
    {
        return array_merge([
            'status' => 2,
            'started' => 1500000000,
            'job_id' => null,
            'job_started' => 0,
            'processed' => 0,
            'cancelled' => 0,
            'failed' => 0,
            'interval' => 5,
            'timeout' => 60,
            'memory' => 1048576,
            'memory_limit' => 128,
        ], $overrides);
    }
}
