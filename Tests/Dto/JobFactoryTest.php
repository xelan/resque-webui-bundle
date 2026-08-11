<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;
use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisAdapter;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisClient;

class JobFactoryTest extends TestCase
{
    public function testCreateByIdReturnsNullForAnUnknownJob()
    {
        $factory = $this->createFactory(new FakeRedisClient());

        $this->assertNull($factory->createById('missing'));
    }

    public function testCreateByIdReadsThePrefixedJobKey()
    {
        $factory = $this->createFactory(new FakeRedisClient([], ['job:abc123' => $this->createHash()]));

        $job = $factory->createById('abc123');

        $this->assertInstanceOf(Job::class, $job);
        $this->assertSame('abc123', $job->getId());
        $this->assertSame('3', $job->getStatus());
        $this->assertSame('emails', $job->getQueue());
        $this->assertSame('host:1:default', $job->getWorker());
        $this->assertSame('{"class":"SendMail"}', $job->getPayload());
    }

    /**
     * @dataProvider optionalFieldProvider
     */
    public function testAnAbsentOptionalFieldBecomesNull($field, $getter)
    {
        $hash = $this->createHash();
        unset($hash[$field]);

        $factory = $this->createFactory(new FakeRedisClient([], ['job:abc123' => $hash]));

        $this->assertNull($factory->createById('abc123')->{$getter}());
    }

    public function optionalFieldProvider()
    {
        return [
            'worker' => ['worker', 'getWorker'],
            'payload' => ['payload', 'getPayload'],
            'exception' => ['exception', 'getException'],
            'created' => ['created', 'getCreated'],
            'started' => ['started', 'getStarted'],
            'updated' => ['updated', 'getUpdated'],
            'finished' => ['finished', 'getFinished'],
        ];
    }

    public function testCreateAllReturnsAnEmptyListWithoutJobs()
    {
        $this->assertSame([], $this->createFactory(new FakeRedisClient())->createAll());
    }

    public function testCreateAllBuildsAJobPerKey()
    {
        $client = new FakeRedisClient(
            ['job:abc123', 'job:def456'],
            [
                'job:abc123' => $this->createHash(['id' => 'abc123']),
                'job:def456' => $this->createHash(['id' => 'def456']),
            ]
        );

        $jobs = $this->createFactory($client)->createAll();

        $this->assertCount(2, $jobs);
        $this->assertSame(['abc123', 'def456'], [$jobs[0]->getId(), $jobs[1]->getId()]);
    }

    /**
     * A job may expire between listing the keys and reading the hash. Those
     * gaps must be skipped instead of ending up as null in the list.
     */
    public function testCreateAllSkipsJobsThatDisappearedAfterListing()
    {
        $client = new FakeRedisClient(
            ['job:abc123', 'job:vanished', 'job:def456'],
            [
                'job:abc123' => $this->createHash(['id' => 'abc123']),
                'job:def456' => $this->createHash(['id' => 'def456']),
            ]
        );

        $jobs = $this->createFactory($client)->createAll();

        $this->assertCount(2, $jobs);
        $this->assertNotContains(null, $jobs);
        $this->assertSame(['abc123', 'def456'], [$jobs[0]->getId(), $jobs[1]->getId()]);
    }

    public function testCreateAllReturnsNothingWhenEveryJobDisappeared()
    {
        $client = new FakeRedisClient(['job:gone-1', 'job:gone-2']);

        $this->assertSame([], $this->createFactory($client)->createAll());
    }

    /**
     * Redis returns the keys in no particular order, so the newest job has to
     * end up on top without any criteria being asked for.
     */
    public function testCreateAllOrdersTheJobsByTheirCreationDescendingByDefault()
    {
        $jobs = $this->createFactory($this->createClientWith([
            'older' => ['created' => '1500000000'],
            'newest' => ['created' => '1500000900'],
            'newer' => ['created' => '1500000500'],
        ]))->createAll();

        $this->assertSame(['newest', 'newer', 'older'], $this->idsOf($jobs));
    }

    /**
     * @dataProvider orderProvider
     */
    public function testCreateAllOrdersTheJobsByTheGivenField($field, $direction, array $hashes, array $expected)
    {
        $criteria = new JobCriteria(null, $field, $direction);

        $jobs = $this->createFactory($this->createClientWith($hashes))->createAll($criteria);

        $this->assertSame($expected, $this->idsOf($jobs));
    }

    public function orderProvider()
    {
        $byCreated = [
            'a' => ['created' => '1500000200'],
            'b' => ['created' => '1500000100'],
            'c' => ['created' => '1500000300'],
        ];
        $byQueue = [
            'a' => ['queue' => 'mails'],
            'b' => ['queue' => 'exports'],
            'c' => ['queue' => 'reports'],
        ];

        return [
            'created ascending' => ['created', 'asc', $byCreated, ['b', 'a', 'c']],
            'created descending' => ['created', 'desc', $byCreated, ['c', 'a', 'b']],
            'queue ascending' => ['queue', 'asc', $byQueue, ['b', 'a', 'c']],
            'queue descending' => ['queue', 'desc', $byQueue, ['c', 'a', 'b']],
            'id ascending' => ['id', 'asc', $byCreated, ['a', 'b', 'c']],
            'id descending' => ['id', 'desc', $byCreated, ['c', 'b', 'a']],
        ];
    }

    /**
     * The timestamps arrive as strings and have to be compared as numbers, or
     * anything below ten sorts above everything else.
     */
    public function testTheTimestampsAreComparedAsNumbers()
    {
        $jobs = $this->createFactory($this->createClientWith([
            'nine' => ['created' => '9'],
            'ten' => ['created' => '10'],
            'hundred' => ['created' => '100'],
        ]))->createAll(new JobCriteria(null, 'created', 'asc'));

        $this->assertSame(['nine', 'ten', 'hundred'], $this->idsOf($jobs));
    }

    /**
     * @dataProvider directionProvider
     */
    public function testJobsWithoutAValueAreAlwaysLast($direction)
    {
        $jobs = $this->createFactory($this->createClientWith([
            'unfinished' => ['finished' => ''],
            'early' => ['finished' => '1500000100'],
            'late' => ['finished' => '1500000200'],
        ]))->createAll(new JobCriteria(null, 'finished', $direction));

        $this->assertSame('unfinished', end($jobs)->getId(), 'the job without a value has to be last');
    }

    public function directionProvider()
    {
        return [
            'ascending' => ['asc'],
            'descending' => ['desc'],
        ];
    }

    /**
     * usort is not stable before PHP 8, so equal values need a tiebreaker to
     * order the same way on every supported version.
     */
    public function testJobsThatCompareEqualAreOrderedByTheirId()
    {
        $hashes = ['c' => [], 'a' => [], 'b' => []];

        $ascending = $this->createFactory($this->createClientWith($hashes))
            ->createAll(new JobCriteria(null, 'created', 'asc'));
        $descending = $this->createFactory($this->createClientWith($hashes))
            ->createAll(new JobCriteria(null, 'created', 'desc'));

        $this->assertSame(['a', 'b', 'c'], $this->idsOf($ascending));
        $this->assertSame(['a', 'b', 'c'], $this->idsOf($descending));
    }

    private function idsOf(array $jobs)
    {
        return array_map(function (Job $job) {
            return $job->getId();
        }, $jobs);
    }

    private function createClientWith(array $hashes)
    {
        $keys = [];
        $contents = [];

        foreach ($hashes as $id => $overrides) {
            $keys[] = 'job:' . $id;
            $contents['job:' . $id] = $this->createHash(array_merge(['id' => $id], $overrides));
        }

        return new FakeRedisClient($keys, $contents);
    }

    private function createFactory(FakeRedisClient $client)
    {
        return new JobFactory(new JobAdapter(), FakeRedisAdapter::withClient($client));
    }

    private function createHash(array $overrides = [])
    {
        return array_merge([
            'id' => 'abc123',
            'status' => '3',
            'queue' => 'emails',
            'worker' => 'host:1:default',
            'payload' => '{"class":"SendMail"}',
            'exception' => '',
            'created' => '1500000000',
            'started' => '1500000001',
            'updated' => '1500000002',
            'finished' => '',
        ], $overrides);
    }
}
