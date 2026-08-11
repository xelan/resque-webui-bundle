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
