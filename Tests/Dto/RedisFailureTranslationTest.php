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
use Predis\Response\ServerException;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;
use Andaris\ResqueWebUiBundle\Adapter\QueueAdapter;
use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;
use Andaris\ResqueWebUiBundle\Exception\RedisUnavailableException;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisAdapter;
use Andaris\ResqueWebUiBundle\Tests\Double\FakeRedisClient;

/**
 * The controllers must not have to know which client php-resque talks to, so
 * the failures of that client are translated where they happen.
 */
class RedisFailureTranslationTest extends TestCase
{
    /**
     * @dataProvider factoryProvider
     */
    public function testACommunicationFailureIsTranslated($build, $call)
    {
        $factory = $build($this->failingClient($this->connectionFailure()));

        $this->expectException(RedisUnavailableException::class);
        $this->expectExceptionMessage('Connection refused [tcp://127.0.0.1:6379]');

        $call($factory);
    }

    /**
     * A server that answers with an error is reachable, so that failure has to
     * keep its own meaning.
     *
     * @dataProvider factoryProvider
     */
    public function testAServerErrorIsNotMistakenForAnUnreachableServer($build, $call)
    {
        $factory = $build($this->failingClient(new ServerException('NOAUTH Authentication required.')));

        $this->expectException(ServerException::class);

        $call($factory);
    }

    public function factoryProvider()
    {
        return [
            'jobs' => [
                function ($client) {
                    return new JobFactory(new JobAdapter(), FakeRedisAdapter::withClient($client));
                },
                function ($factory) {
                    return $factory->createAll();
                },
            ],
            'job by id' => [
                function ($client) {
                    return new JobFactory(new JobAdapter(), FakeRedisAdapter::withClient($client));
                },
                function ($factory) {
                    return $factory->createById('abc123');
                },
            ],
            'queues' => [
                function ($client) {
                    return new QueueFactory(new QueueAdapter(), FakeRedisAdapter::withClient($client));
                },
                function ($factory) {
                    return $factory->createAll();
                },
            ],
        ];
    }

    public function testTheWorkerFactoryTranslatesTheFailureOfTheAdapter()
    {
        $adapter = $this->createMock(WorkerAdapter::class);
        $adapter->method('allWorkers')->willThrowException($this->connectionFailure());

        $this->expectException(RedisUnavailableException::class);

        (new WorkerFactory($adapter))->createAll();
    }

    private function connectionFailure()
    {
        return new ConnectionException(
            $this->createMock('Predis\Connection\NodeConnectionInterface'),
            'Connection refused [tcp://127.0.0.1:6379]'
        );
    }

    /**
     * A client that fails on every command, the way an unreachable server does.
     */
    private function failingClient($failure)
    {
        return new class($failure) extends FakeRedisClient {
            private $failure;

            public function __construct($failure)
            {
                parent::__construct();

                $this->failure = $failure;
            }

            public function keys($pattern)
            {
                throw $this->failure;
            }

            public function hgetall($key)
            {
                throw $this->failure;
            }

            public function smembers($key)
            {
                throw $this->failure;
            }
        };
    }
}
