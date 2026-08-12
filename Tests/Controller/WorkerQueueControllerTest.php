<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Controller\QueueController;
use Andaris\ResqueWebUiBundle\Controller\WorkerController;
use Andaris\ResqueWebUiBundle\Dto\QueueCriteria;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Dto\WorkerCriteria;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;

class WorkerQueueControllerTest extends TestCase
{
    /**
     * @var array
     */
    private $parameters;

    public function testTheWorkerListHandsTheCriteriaToTheFactoryAndTheTemplate()
    {
        $factory = $this->createMock(WorkerFactory::class);
        $factory->expects($this->once())
            ->method('createAll')
            ->with($this->callback(function (WorkerCriteria $criteria) {
                return $criteria->getField() === 'memory' && $criteria->isDescending();
            }))
            ->willReturn([]);

        $controller = new WorkerController(
            $this->createTwig(),
            $this->createMock(ResqueConfigurator::class),
            $factory
        );

        $controller->indexAction(Request::create('/workers?sort=memory&direction=desc'));

        $this->assertInstanceOf(WorkerCriteria::class, $this->parameters['criteria']);
        $this->assertSame('memory', $this->parameters['criteria']->getField());
    }

    public function testTheQueueListHandsTheCriteriaToTheFactoryAndTheTemplate()
    {
        $factory = $this->createMock(QueueFactory::class);
        $factory->expects($this->once())
            ->method('createAll')
            ->with($this->callback(function (QueueCriteria $criteria) {
                return $criteria->getField() === 'total' && !$criteria->isDescending();
            }))
            ->willReturn([]);

        $controller = new QueueController(
            $this->createTwig(),
            $this->createMock(ResqueConfigurator::class),
            $factory
        );

        $controller->indexAction(Request::create('/queues?sort=total&direction=asc'));

        $this->assertInstanceOf(QueueCriteria::class, $this->parameters['criteria']);
        $this->assertSame('total', $this->parameters['criteria']->getField());
    }

    public function testTheDefaultsAreUsedWithoutQueryParameters()
    {
        $workerFactory = $this->createMock(WorkerFactory::class);
        $workerFactory->method('createAll')->willReturn([]);

        (new WorkerController($this->createTwig(), $this->createMock(ResqueConfigurator::class), $workerFactory))
            ->indexAction(Request::create('/workers'));

        $this->assertSame('id', $this->parameters['criteria']->getField());
        $this->assertFalse($this->parameters['criteria']->isDescending());

        $queueFactory = $this->createMock(QueueFactory::class);
        $queueFactory->method('createAll')->willReturn([]);

        (new QueueController($this->createTwig(), $this->createMock(ResqueConfigurator::class), $queueFactory))
            ->indexAction(Request::create('/queues'));

        $this->assertSame('name', $this->parameters['criteria']->getField());
        $this->assertFalse($this->parameters['criteria']->isDescending());
    }

    private function createTwig()
    {
        $parameters = &$this->parameters;

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function ($view, array $given = []) use (&$parameters) {
            $parameters = $given;

            return '';
        });

        return $twig;
    }
}
