<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Predis\Connection\ConnectionException;
use Resque\Job as ResqueJob;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;
use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;
use Andaris\ResqueWebUiBundle\Exception\JobNotRepeatableException;
use Andaris\ResqueWebUiBundle\Exception\RedisUnavailableException;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisAdapter;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisClient;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeResqueJob;

class JobRetryTest extends TestCase
{
    const PAYLOAD = '{"id":"old123","class":"App\\\\Job\\\\SendMail","data":{"to":"someone@example.org"}}';

    /**
     * The new job carries the class and the arguments of the old one onto the
     * same queue, and is a job of its own with an id of its own.
     */
    public function testItQueuesTheSameWorkAgain()
    {
        $adapter = $this->createMock(JobAdapter::class);
        $adapter->expects($this->once())
            ->method('create')
            ->with('emails', 'App\Job\SendMail', ['to' => 'someone@example.org'])
            ->willReturn($this->resqueJob('new456'));

        $factory = $this->createFactory($adapter, ['job:new456' => $this->hash('new456')]);

        $retried = $factory->recreate($this->job(self::PAYLOAD));

        $this->assertInstanceOf(Job::class, $retried);
        $this->assertSame('new456', $retried->getId());
    }

    /**
     * @dataProvider unusablePayloadProvider
     */
    public function testAPayloadItCannotReadIsRefused($payload, $expected)
    {
        $adapter = $this->createMock(JobAdapter::class);
        $adapter->expects($this->never())->method('create');

        $this->expectException(JobNotRepeatableException::class);
        $this->expectExceptionMessage($expected);

        $this->createFactory($adapter)->recreate($this->job($payload));
    }

    public function unusablePayloadProvider()
    {
        return [
            'not json' => ['not json at all', 'not valid JSON'],
            'empty' => ['', 'not valid JSON'],
            'null' => [null, 'not valid JSON'],
            'a scalar' => ['"a string"', 'not valid JSON'],
            'no class' => ['{"id":"old123","data":[]}', 'does not name the class'],
            'empty class' => ['{"class":""}', 'does not name the class'],
            'class is an array' => ['{"class":["App\\\\Job"]}', 'does not name the class'],
        ];
    }

    /**
     * A job without arguments is queued without arguments rather than with an
     * empty array, which is what php-resque itself does.
     */
    public function testAJobWithoutArgumentsIsQueuedWithoutThem()
    {
        $adapter = $this->createMock(JobAdapter::class);
        $adapter->expects($this->once())
            ->method('create')
            ->with('emails', 'App\Job\SendMail', null)
            ->willReturn($this->resqueJob('new456'));

        $factory = $this->createFactory($adapter, ['job:new456' => $this->hash('new456')]);

        $factory->recreate($this->job('{"id":"old123","class":"App\\\\Job\\\\SendMail"}'));
    }

    /**
     * php-resque lets a listener refuse to queue a job, which it reports by
     * handing back no job at all.
     */
    public function testAQueueingThatWasRefusedIsReported()
    {
        $adapter = $this->createMock(JobAdapter::class);
        $adapter->method('create')->willReturn(null);

        $this->expectException(JobNotRepeatableException::class);
        $this->expectExceptionMessage('was refused');

        $this->createFactory($adapter)->recreate($this->job(self::PAYLOAD));
    }

    public function testAnUnreachableServerIsReportedAsSuch()
    {
        $adapter = $this->createMock(JobAdapter::class);
        $adapter->method('create')->willThrowException(new ConnectionException(
            $this->createMock('Predis\Connection\NodeConnectionInterface'),
            'Connection refused [tcp://127.0.0.1:6379]'
        ));

        $this->expectException(RedisUnavailableException::class);

        $this->createFactory($adapter)->recreate($this->job(self::PAYLOAD));
    }

    private function createFactory(JobAdapter $adapter, array $hashes = [])
    {
        return new JobFactory($adapter, FakeRedisAdapter::withClient(new FakeRedisClient([], $hashes)));
    }

    private function job($payload)
    {
        return new Job('old123', ResqueJob::STATUS_FAILED, 'emails', null, $payload, null, null, null, null, null);
    }

    private function resqueJob($id)
    {
        return new FakeResqueJob($id);
    }

    private function hash($id)
    {
        return [
            'id' => $id,
            'status' => (string) ResqueJob::STATUS_WAITING,
            'queue' => 'emails',
            'payload' => self::PAYLOAD,
            'created' => '1500000000',
        ];
    }
}
