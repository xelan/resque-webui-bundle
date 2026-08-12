<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Predis\Connection\ConnectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Controller\JobController;
use Andaris\ResqueWebUiBundle\Controller\MetricsController;
use Andaris\ResqueWebUiBundle\Controller\QueueController;
use Andaris\ResqueWebUiBundle\Controller\WorkerController;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;
use Andaris\ResqueWebUiBundle\Exception\RedisUnavailableException;

/**
 * Every list of the interface reads from Redis, so an unreachable server used
 * to end each of them in an uncaught exception.
 */
class RedisUnavailableTest extends TestCase
{
    const MESSAGE = 'Connection refused [tcp://127.0.0.1:6379]';

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string
     */
    private $view;

    /**
     * @dataProvider listProvider
     */
    public function testTheListSaysSoInsteadOfFailing($controllerClass, $factoryClass, $method, $argument)
    {
        $factory = $this->createMock($factoryClass);
        $factory->method($method)->willThrowException($this->failure());

        $controller = new $controllerClass(
            $this->createTwig(),
            $this->createMock(ResqueConfigurator::class),
            $factory
        );

        $response = $controller->{$argument['action']}($argument['argument']);

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertSame('@AndarisResqueWebUi/Error/redis.html.twig', $this->view);
        $this->assertSame(self::MESSAGE, $this->parameters['error']);
    }

    public function listProvider()
    {
        return [
            'workers' => [
                WorkerController::class,
                WorkerFactory::class,
                'createAll',
                ['action' => 'indexAction', 'argument' => Request::create('/workers')],
            ],
            'queues' => [
                QueueController::class,
                QueueFactory::class,
                'createAll',
                ['action' => 'indexAction', 'argument' => Request::create('/queues')],
            ],
            'jobs' => [
                JobController::class,
                JobFactory::class,
                'createAll',
                ['action' => 'indexAction', 'argument' => Request::create('/jobs')],
            ],
            'job details' => [
                JobController::class,
                JobFactory::class,
                'createById',
                ['action' => 'detailsAction', 'argument' => 'abc123'],
            ],
        ];
    }

    public function testThePageNamesTheConfigurationInUse()
    {
        $configurator = $this->createMock(ResqueConfigurator::class);
        $configurator->method('getConfigFile')->willReturn('/srv/app/config/resque.yml');

        $factory = $this->createMock(QueueFactory::class);
        $factory->method('createAll')->willThrowException($this->failure());

        (new QueueController($this->createTwig(), $configurator, $factory))
            ->indexAction(Request::create('/queues'));

        $this->assertSame('/srv/app/config/resque.yml', $this->parameters['configFile']);
    }

    public function testThePageCopesWithoutAConfigurationFile()
    {
        $configurator = $this->createMock(ResqueConfigurator::class);
        $configurator->method('getConfigFile')->willReturn(null);

        $factory = $this->createMock(QueueFactory::class);
        $factory->method('createAll')->willThrowException($this->failure());

        (new QueueController($this->createTwig(), $configurator, $factory))
            ->indexAction(Request::create('/queues'));

        $this->assertNull($this->parameters['configFile']);
    }

    /**
     * A scrape wants a status it can alert on rather than a page.
     */
    public function testTheMetricsEndpointAnswersWithAStatus()
    {
        $queueFactory = $this->createMock(QueueFactory::class);
        $queueFactory->method('createAll')->willThrowException($this->failure());

        $controller = new MetricsController(
            $this->createMock(ResqueConfigurator::class),
            $queueFactory,
            $this->createMock(WorkerFactory::class)
        );

        $response = $controller->exportPrometheusAction();

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no connection to redis', $response->getContent());
        $this->assertStringStartsWith('#', $response->getContent(), 'a scrape reads it as a comment');
    }

    public function testTheFailureCarriesTheReportOfTheClient()
    {
        $failure = RedisUnavailableException::fromCommunicationFailure(
            new ConnectionException($this->createMock('Predis\Connection\NodeConnectionInterface'), self::MESSAGE)
        );

        $this->assertSame(self::MESSAGE, $failure->getMessage());
        $this->assertInstanceOf(ConnectionException::class, $failure->getPrevious());
    }

    private function failure()
    {
        return RedisUnavailableException::fromCommunicationFailure(
            new ConnectionException($this->createMock('Predis\Connection\NodeConnectionInterface'), self::MESSAGE)
        );
    }

    private function createTwig()
    {
        $parameters = &$this->parameters;
        $view = &$this->view;

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(
            function ($template, array $given = []) use (&$parameters, &$view) {
                $view = $template;
                $parameters = $given;

                return '';
            }
        );

        return $twig;
    }
}
