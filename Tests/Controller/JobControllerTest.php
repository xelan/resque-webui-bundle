<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Resque\Job as ResqueJob;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Controller\JobController;
use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;

class JobControllerTest extends TestCase
{
    /**
     * @var array
     */
    private $parameters;

    public function testTheListShowsEveryJobWithoutAFilter()
    {
        $this->renderIndex(Request::create('/jobs'), [
            $this->createJob('a', ResqueJob::STATUS_FAILED),
            $this->createJob('b', ResqueJob::STATUS_COMPLETE),
        ]);

        $this->assertSame(['a', 'b'], $this->renderedJobIds());
        $this->assertSame(2, $this->parameters['total']);
    }

    public function testOnlyTheJobsOfTheSelectedStatusAreShown()
    {
        $this->renderIndex(Request::create('/jobs?status=' . ResqueJob::STATUS_FAILED), [
            $this->createJob('a', ResqueJob::STATUS_FAILED),
            $this->createJob('b', ResqueJob::STATUS_COMPLETE),
            $this->createJob('c', ResqueJob::STATUS_FAILED),
        ]);

        $this->assertSame(['a', 'c'], $this->renderedJobIds());
    }

    /**
     * The counts next to the filter describe every job there is to pick from,
     * not the ones that are left after picking a status.
     */
    public function testTheCountsCoverEveryStatusRegardlessOfTheStatusFilter()
    {
        $this->renderIndex(Request::create('/jobs?status=' . ResqueJob::STATUS_FAILED), [
            $this->createJob('a', ResqueJob::STATUS_FAILED),
            $this->createJob('b', ResqueJob::STATUS_COMPLETE),
            $this->createJob('c', ResqueJob::STATUS_FAILED),
        ]);

        $this->assertSame(2, $this->parameters['counts'][ResqueJob::STATUS_FAILED]);
        $this->assertSame(1, $this->parameters['counts'][ResqueJob::STATUS_COMPLETE]);
        $this->assertSame(0, $this->parameters['counts'][ResqueJob::STATUS_WAITING]);
        $this->assertSame(3, $this->parameters['total']);
    }

    public function testOnlyTheJobsOfTheSelectedQueueAreShown()
    {
        $this->renderIndex(Request::create('/jobs?queue=emails'), [
            $this->createJob('a', ResqueJob::STATUS_FAILED, 'emails'),
            $this->createJob('b', ResqueJob::STATUS_FAILED, 'reports'),
            $this->createJob('c', ResqueJob::STATUS_COMPLETE, 'emails'),
        ]);

        $this->assertSame(['a', 'c'], $this->renderedJobIds());
    }

    public function testTheQueueFilterAndTheStatusFilterApplyTogether()
    {
        $this->renderIndex(Request::create('/jobs?queue=emails&status=' . ResqueJob::STATUS_FAILED), [
            $this->createJob('a', ResqueJob::STATUS_FAILED, 'emails'),
            $this->createJob('b', ResqueJob::STATUS_FAILED, 'reports'),
            $this->createJob('c', ResqueJob::STATUS_COMPLETE, 'emails'),
        ]);

        $this->assertSame(['a'], $this->renderedJobIds());
    }

    /**
     * The counts sit above a list that shows one queue, so counting the jobs of
     * every queue would have them contradict the table below them.
     */
    public function testTheCountsAndTheTotalDescribeTheSelectedQueue()
    {
        $this->renderIndex(Request::create('/jobs?queue=emails&status=' . ResqueJob::STATUS_FAILED), [
            $this->createJob('a', ResqueJob::STATUS_FAILED, 'emails'),
            $this->createJob('b', ResqueJob::STATUS_FAILED, 'reports'),
            $this->createJob('c', ResqueJob::STATUS_COMPLETE, 'emails'),
            $this->createJob('d', ResqueJob::STATUS_COMPLETE, 'reports'),
        ]);

        $this->assertSame(1, $this->parameters['counts'][ResqueJob::STATUS_FAILED]);
        $this->assertSame(1, $this->parameters['counts'][ResqueJob::STATUS_COMPLETE]);
        $this->assertSame(2, $this->parameters['total']);
    }

    public function testTheQueueIsHandedToTheTemplateThroughTheCriteria()
    {
        $this->renderIndex(Request::create('/jobs?queue=emails'), []);

        $this->assertSame('emails', $this->parameters['criteria']->getQueue());
    }

    public function testEveryStatusIsListedEvenWithoutJobs()
    {
        $this->renderIndex(Request::create('/jobs'), []);

        $this->assertSame(JobCriteria::STATUSES, array_keys($this->parameters['counts']));
        $this->assertSame([0, 0, 0, 0, 0, 0], array_values($this->parameters['counts']));
        $this->assertSame(0, $this->parameters['total']);
    }

    public function testTheCriteriaAreHandedToTheTemplate()
    {
        $this->renderIndex(Request::create('/jobs?sort=queue&direction=asc'), []);

        $this->assertInstanceOf(JobCriteria::class, $this->parameters['criteria']);
        $this->assertSame('queue', $this->parameters['criteria']->getField());
        $this->assertSame(JobCriteria::DIRECTION_ASCENDING, $this->parameters['criteria']->getDirection());
    }

    public function testTheCriteriaAreHandedToTheFactory()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->expects($this->once())
            ->method('createAll')
            ->with($this->callback(function (JobCriteria $criteria) {
                return $criteria->getField() === 'worker' && $criteria->isDescending();
            }))
            ->willReturn([]);

        $this->createController($factory)->indexAction(Request::create('/jobs?sort=worker&direction=desc'));
    }

    public function testAnUnknownJobIsNotFound()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Job not found!');

        $this->createController($factory)->detailsAction('missing', Request::create('/job/missing'));
    }

    private function renderIndex(Request $request, array $jobs)
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createAll')->willReturn($jobs);

        $this->createController($factory)->indexAction($request);
    }

    private function renderedJobIds()
    {
        return array_map(function (Job $job) {
            return $job->getId();
        }, $this->parameters['jobs']);
    }

    private function createController(JobFactory $factory)
    {
        $parameters = &$this->parameters;

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function ($view, array $given = []) use (&$parameters) {
            $parameters = $given;

            return '';
        });

        return new JobController(
            $twig,
            $this->createMock(ResqueConfigurator::class),
            $factory,
            $this->createCsrf(),
            $this->createRouter()
        );
    }

    private function createCsrf()
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('getToken')->willReturn(new CsrfToken('job_retry', 'token'));
        $csrf->method('isTokenValid')->willReturn(true);

        return $csrf;
    }

    private function createRouter()
    {
        return $this->createMock(UrlGeneratorInterface::class);
    }

    private function createJob($id, $status, $queue = 'emails')
    {
        return new Job($id, $status, $queue, null, null, null, 1500000000, null, null, null);
    }
}
