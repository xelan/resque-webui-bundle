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
use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;
use Andaris\ResqueWebUiBundle\Dto\QueueCriteria;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Dto\WorkerCriteria;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisAdapter;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisClient;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeResqueWorker;

class WorkerQueueSortTest extends TestCase
{
    /**
     * Redis returns the workers in no particular order.
     */
    public function testTheWorkersAreOrderedByTheirIdByDefault()
    {
        $workers = $this->createWorkerFactory(['host:3', 'host:1', 'host:2'])->createAll();

        $this->assertSame(['host:1', 'host:2', 'host:3'], $this->idsOf($workers));
    }

    /**
     * @dataProvider workerOrderProvider
     */
    public function testTheWorkersAreOrderedByTheGivenField($field, $direction, array $packets, array $expected)
    {
        $factory = $this->createWorkerFactory(array_keys($packets), $packets);

        $workers = $factory->createAll(new WorkerCriteria($field, $direction));

        $this->assertSame($expected, $this->idsOf($workers));
    }

    public function workerOrderProvider()
    {
        $byMemory = [
            'host:1' => ['memory' => 300],
            'host:2' => ['memory' => 100],
            'host:3' => ['memory' => 200],
        ];

        return [
            'id descending' => ['id', 'desc', $byMemory, ['host:3', 'host:2', 'host:1']],
            'memory ascending' => ['memory', 'asc', $byMemory, ['host:2', 'host:3', 'host:1']],
            'memory descending' => ['memory', 'desc', $byMemory, ['host:1', 'host:3', 'host:2']],
        ];
    }

    /**
     * The counters arrive from Redis as strings and have to be compared as
     * numbers, or anything below ten sorts above everything else.
     */
    public function testTheWorkerCountersAreComparedAsNumbers()
    {
        $factory = $this->createWorkerFactory(['a', 'b', 'c'], [
            'a' => ['processed' => '9'],
            'b' => ['processed' => '100'],
            'c' => ['processed' => '10'],
        ]);

        $workers = $factory->createAll(new WorkerCriteria('processed', 'asc'));

        $this->assertSame(['a', 'c', 'b'], $this->idsOf($workers));
    }

    /**
     * @dataProvider directionProvider
     */
    public function testWorkersWithoutACurrentJobAreAlwaysLast($direction)
    {
        $factory = $this->createWorkerFactory(['idle', 'busy'], [
            'idle' => ['job_id' => ''],
            'busy' => ['job_id' => 'abc123'],
        ]);

        $workers = $factory->createAll(new WorkerCriteria('job', $direction));

        $this->assertSame('idle', end($workers)->getId());
    }

    public function directionProvider()
    {
        return [
            'ascending' => ['asc'],
            'descending' => ['desc'],
        ];
    }

    /**
     * The column shows how long a worker has been running, so the shortest one
     * belongs at the top of an ascending list even though it carries the
     * highest start time.
     */
    public function testTheShortestRunningWorkerIsFirstWhenSortedAscending()
    {
        $factory = $this->createWorkerFactory(['old', 'young', 'middle'], [
            'old' => ['started' => 1500000000],
            'young' => ['started' => 1500000200],
            'middle' => ['started' => 1500000100],
        ]);

        $workers = $factory->createAll(new WorkerCriteria('started', 'asc'));

        $this->assertSame(['young', 'middle', 'old'], $this->idsOf($workers));
    }

    public function testTheLongestRunningWorkerIsFirstWhenSortedDescending()
    {
        $factory = $this->createWorkerFactory(['old', 'young', 'middle'], [
            'old' => ['started' => 1500000000],
            'young' => ['started' => 1500000200],
            'middle' => ['started' => 1500000100],
        ]);

        $workers = $factory->createAll(new WorkerCriteria('started', 'desc'));

        $this->assertSame(['old', 'middle', 'young'], $this->idsOf($workers));
    }

    /**
     * Turning the comparison around must not lift the workers without a start
     * time off the bottom of the list.
     *
     * @dataProvider directionProvider
     */
    public function testWorkersWithoutAStartTimeAreAlwaysLast($direction)
    {
        $factory = $this->createWorkerFactory(['unknown', 'known'], [
            'unknown' => ['started' => ''],
            'known' => ['started' => 1500000000],
        ]);

        $workers = $factory->createAll(new WorkerCriteria('started', $direction));

        $this->assertSame('unknown', end($workers)->getId());
    }

    /**
     * Only the duration behind the worker list runs the other way round; the
     * job list shows its timestamps as they are.
     */
    public function testTheStartTimeIsTheOnlyValueRunningOppositeToItsColumn()
    {
        $this->assertTrue((new WorkerCriteria('started'))->isInvertedField());
        $this->assertFalse((new WorkerCriteria('memory'))->isInvertedField());
        $this->assertFalse((new JobCriteria('started'))->isInvertedField());
        $this->assertFalse((new QueueCriteria('name'))->isInvertedField());
    }

    public function testTheQueuesAreOrderedByTheirNameByDefault()
    {
        $queues = $this->createQueueFactory(['reports', 'emails', 'imports'])->createAll();

        $this->assertSame(['emails', 'imports', 'reports'], $this->namesOf($queues));
    }

    /**
     * @dataProvider queueOrderProvider
     */
    public function testTheQueuesAreOrderedByTheGivenField($field, $direction, array $expected)
    {
        $stats = [
            'emails' => ['queued' => '5', 'failed' => '1'],
            'imports' => ['queued' => '30', 'failed' => '0'],
            'reports' => ['queued' => '9', 'failed' => '7'],
        ];

        $queues = $this->createQueueFactory(array_keys($stats), $stats)
            ->createAll(new QueueCriteria($field, $direction));

        $this->assertSame($expected, $this->namesOf($queues));
    }

    public function queueOrderProvider()
    {
        return [
            'name descending' => ['name', 'desc', ['reports', 'imports', 'emails']],
            'queued ascending' => ['queued', 'asc', ['emails', 'reports', 'imports']],
            'queued descending' => ['queued', 'desc', ['imports', 'reports', 'emails']],
            'failed ascending' => ['failed', 'asc', ['imports', 'emails', 'reports']],
            'total descending' => ['total', 'desc', ['imports', 'reports', 'emails']],
        ];
    }

    /**
     * usort is not stable before PHP 8, so equal values need a tiebreaker to
     * order the same way on every supported version.
     */
    public function testQueuesThatCompareEqualAreOrderedByTheirName()
    {
        $names = ['c', 'a', 'b'];

        $ascending = $this->createQueueFactory($names)->createAll(new QueueCriteria('queued', 'asc'));
        $descending = $this->createQueueFactory($names)->createAll(new QueueCriteria('queued', 'desc'));

        $this->assertSame(['a', 'b', 'c'], $this->namesOf($ascending));
        $this->assertSame(['a', 'b', 'c'], $this->namesOf($descending));
    }

    private function createWorkerFactory(array $ids, array $packets = [])
    {
        $workers = [];

        foreach ($ids as $id) {
            $workers[] = new FakeResqueWorker($id, array_merge([
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
            ], isset($packets[$id]) ? $packets[$id] : []));
        }

        $adapter = $this->createMock(WorkerAdapter::class);
        $adapter->method('allWorkers')->willReturn($workers);

        return new WorkerFactory($adapter);
    }

    private function createQueueFactory(array $names, array $stats = [])
    {
        $hashes = [];

        foreach ($names as $name) {
            $hashes['queue:' . $name . ':stats'] = isset($stats[$name]) ? $stats[$name] : [];
        }

        $client = new FakeRedisClient([], $hashes, ['queues' => $names]);

        return new QueueFactory(new QueueAdapter(), FakeRedisAdapter::withClient($client));
    }

    private function idsOf(array $workers)
    {
        return array_map(function ($worker) {
            return $worker->getId();
        }, $workers);
    }

    private function namesOf(array $queues)
    {
        return array_map(function ($queue) {
            return $queue->getName();
        }, $queues);
    }
}
