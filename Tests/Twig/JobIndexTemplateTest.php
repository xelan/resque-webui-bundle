<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

use Resque\Job as ResqueJob;

use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;

/**
 * Renders the job list against a stubbed layout, so that the sorting links and
 * the status filter are covered rather than only parsed.
 */
class JobIndexTemplateTest extends ListTemplateTestCase
{
    public function testItListsTheJobs()
    {
        $output = $this->render(new JobCriteria(), [
            $this->createJob('abc123', ResqueJob::STATUS_FAILED),
            $this->createJob('def456', ResqueJob::STATUS_COMPLETE),
        ]);

        $this->assertStringContainsString('abc123', $output);
        $this->assertStringContainsString('def456', $output);
        $this->assertStringContainsString('Failed', $output);
        $this->assertStringContainsString('Complete', $output);
    }

    public function testEveryColumnHeaderIsALink()
    {
        $output = $this->render(new JobCriteria(), []);

        foreach (['ID', 'Status', 'Queue', 'Worker', 'Created', 'Started', 'Updated', 'Finished'] as $column) {
            $this->assertRegExp('#<a [^>]*>\s*' . $column . '#', $output, $column . ' is not a sorting link');
        }
    }

    public function testTheColumnInUseCarriesTheIndicator()
    {
        $output = $this->render(new JobCriteria('queue', 'asc'), []);

        $this->assertStringContainsString('caret-up', $output);
    }

    public function testTheFilterShowsACountPerStatus()
    {
        $counts = array_fill_keys(JobCriteria::STATUSES, 0);
        $counts[ResqueJob::STATUS_FAILED] = 7;

        $output = $this->render(new JobCriteria(), [], $counts, 7);

        $this->assertRegExp('#Failed\s*<span class="badge">7</span>#', $output);
        $this->assertRegExp('#All\s*<span class="badge">7</span>#', $output);
    }

    public function testTheSelectedStatusIsMarkedAsActive()
    {
        $output = $this->render(new JobCriteria(null, null, ResqueJob::STATUS_FAILED), []);

        $this->assertRegExp('#<li class="active">\s*<a[^>]*>\s*Failed#', $output);
    }

    public function testTheEmptyListMentionsTheFilter()
    {
        $this->assertStringContainsString('No jobs found.', $this->render(new JobCriteria(), []));
        $this->assertStringContainsString(
            'No failed jobs found.',
            $this->render(new JobCriteria(null, null, ResqueJob::STATUS_FAILED), [])
        );
    }

    private function render(JobCriteria $criteria, array $jobs, array $counts = null, $total = null)
    {
        $counts = $counts === null ? array_fill_keys(JobCriteria::STATUSES, 0) : $counts;

        return $this->renderTemplate('Job/index.html.twig', [
            'jobs' => $jobs,
            'criteria' => $criteria,
            'counts' => $counts,
            'total' => $total === null ? count($jobs) : $total,
        ]);
    }
    private function createJob($id, $status)
    {
        return new Job($id, $status, 'emails', 'host:1:default', null, null, 1500000000, null, null, null);
    }
}
