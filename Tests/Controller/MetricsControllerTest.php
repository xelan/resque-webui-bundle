<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Resque\Worker as ResqueWorker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Controller\MetricsController;
use Andaris\ResqueWebUiBundle\Dto\Queue;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Dto\Worker;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;

class MetricsControllerTest extends TestCase
{
    public function testTheConfigurationIsLoadedOnConstruction()
    {
        $configurator = $this->createMock(ResqueConfigurator::class);
        $configurator->expects($this->once())->method('loadConfig');

        new MetricsController(
            $configurator,
            $this->createMock(QueueFactory::class),
            $this->createMock(WorkerFactory::class)
        );
    }

    public function testItAnnouncesThePrometheusExpositionContentType()
    {
        $response = $this->createController()->exportPrometheusAction();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('text/plain; version=0.0.4', $response->headers->get('Content-Type'));
    }

    public function testItExposesEveryQueueCounter()
    {
        $body = $this->createController([new Queue('emails', 1, 2, 3, 4, 5)])->exportPrometheusAction()->getContent();

        $this->assertStringContainsString('resque_queue_queued{queue="emails"} 1', $body);
        $this->assertStringContainsString('resque_queue_delayed{queue="emails"} 2', $body);
        $this->assertStringContainsString('resque_queue_processed{queue="emails"} 3', $body);
        $this->assertStringContainsString('resque_queue_cancelled{queue="emails"} 4', $body);
        $this->assertStringContainsString('resque_queue_failed{queue="emails"} 5', $body);
    }

    public function testEveryQueueMetricIsTyped()
    {
        $body = $this->createController([new Queue('emails', 1, 2, 3, 4, 5)])->exportPrometheusAction()->getContent();

        $this->assertStringContainsString('# TYPE resque_queue_queued gauge', $body);
        $this->assertStringContainsString('# TYPE resque_queue_delayed gauge', $body);
        $this->assertStringContainsString('# TYPE resque_queue_processed counter', $body);
    }

    /**
     * A backslash, a double quote and a line break would break the exposition
     * format, so they have to be escaped in the label value.
     */
    public function testItEscapesTheQueueName()
    {
        $body = $this->createController([new Queue("we\"ird\\name\nbroken", 1, 0, 0, 0, 0)])
            ->exportPrometheusAction()
            ->getContent();

        $this->assertStringContainsString('resque_queue_queued{queue="we\"ird\\\\name\nbroken"} 1', $body);
    }

    public function testItEscapesTheWorkerId()
    {
        $body = $this->createController([], [$this->createWorker("ho\"st:1", ResqueWorker::STATUS_RUNNING)])
            ->exportPrometheusAction()
            ->getContent();

        $this->assertStringContainsString('resque_worker_processed{id="ho\"st:1"}', $body);
    }

    public function testItCountsTheWorkersPerStatus()
    {
        $workers = [
            $this->createWorker('host:1', ResqueWorker::STATUS_NEW),
            $this->createWorker('host:2', ResqueWorker::STATUS_RUNNING),
            $this->createWorker('host:3', ResqueWorker::STATUS_RUNNING),
            $this->createWorker('host:4', ResqueWorker::STATUS_PAUSED),
        ];

        $body = $this->createController([], $workers)->exportPrometheusAction()->getContent();

        $this->assertStringContainsString('resque_worker_new 1' . "\n", $body);
        $this->assertStringContainsString('resque_worker_running 2' . "\n", $body);
        $this->assertStringContainsString('resque_worker_paused 1' . "\n", $body);
    }

    /**
     * The status arrives from Redis as a string and is cast before counting.
     */
    public function testItCountsWorkersWhoseStatusIsANumericString()
    {
        $body = $this->createController([], [$this->createWorker('host:1', '2')])
            ->exportPrometheusAction()
            ->getContent();

        $this->assertStringContainsString('resque_worker_running 1' . "\n", $body);
    }

    public function testItExposesTheWorkerMemoryUsage()
    {
        $body = $this->createController([], [$this->createWorker('host:1', ResqueWorker::STATUS_RUNNING)])
            ->exportPrometheusAction()
            ->getContent();

        $this->assertStringContainsString('resque_worker_mem{id="host:1"} 1048576', $body);
    }

    public function testAPrometheusScrapeIsAnsweredWithTheExpositionFormat()
    {
        $request = Request::create('/metrics');
        $request->headers->set('User-Agent', 'Prometheus/2.45.0');

        $response = $this->createController([new Queue('emails', 1, 0, 0, 0, 0)])->exportAction($request);

        $this->assertSame('text/plain; version=0.0.4', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('resque_queue_queued{queue="emails"} 1', $response->getContent());
    }

    /**
     * A request without any User-Agent header must be rejected rather than fail
     * inside the detection.
     */
    public function testARequestWithoutAUserAgentIsRejected()
    {
        $request = Request::create('/metrics');
        $request->headers->remove('User-Agent');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported request!');

        $this->createController()->exportAction($request);
    }

    /**
     * The detection must not hand a null User-Agent to stripos(). PHP only
     * deprecates that today, so the rejection alone does not prove the fix and
     * the deprecation has to be asserted explicitly.
     */
    public function testAMissingUserAgentIsHandledWithoutADeprecation()
    {
        $controller = $this->createController();

        $request = Request::create('/metrics');
        $request->headers->remove('User-Agent');

        $deprecations = [];
        set_error_handler(function ($number, $message) use (&$deprecations) {
            $deprecations[] = $message;

            return true;
        }, E_DEPRECATED);

        try {
            $controller->exportAction($request);
            $this->fail('Expected the request to be rejected.');
        } catch (BadRequestHttpException $exception) {
            // the rejection itself is covered by testARequestWithoutAUserAgentIsRejected()
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }

    /**
     * @dataProvider foreignUserAgentProvider
     */
    public function testARequestFromAnotherClientIsRejected($userAgent)
    {
        $request = Request::create('/metrics');
        $request->headers->set('User-Agent', $userAgent);

        $this->expectException(BadRequestHttpException::class);

        $this->createController()->exportAction($request);
    }

    public function foreignUserAgentProvider()
    {
        return [
            'browser' => ['Mozilla/5.0'],
            'empty' => [''],
            'similar name' => ['Prometheous/1.0'],
        ];
    }

    private function createController(array $queues = [], array $workers = [])
    {
        $queueFactory = $this->createMock(QueueFactory::class);
        $queueFactory->method('createAll')->willReturn($queues);

        $workerFactory = $this->createMock(WorkerFactory::class);
        $workerFactory->method('createAll')->willReturn($workers);

        return new MetricsController($this->createMock(ResqueConfigurator::class), $queueFactory, $workerFactory);
    }

    private function createWorker($id, $status)
    {
        return new Worker($id, $status, 1500000000, null, 0, 7, 2, 1, 5, 60, 1048576, 128);
    }
}
