<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

use Resque\Worker as ResqueWorker;

use Andaris\ResqueWebUiBundle\Dto\Queue;
use Andaris\ResqueWebUiBundle\Dto\QueueCriteria;
use Andaris\ResqueWebUiBundle\Dto\Worker;
use Andaris\ResqueWebUiBundle\Dto\WorkerCriteria;

class WorkerQueueIndexTemplateTest extends ListTemplateTestCase
{
    public function testTheWorkerListShowsItsWorkers()
    {
        $output = $this->renderWorkers(new WorkerCriteria(), [$this->createWorker('host:1:default')]);

        $this->assertStringContainsString('host:1:default', $output);
        $this->assertStringContainsString('Running', $output);
    }

    public function testEveryWorkerColumnIsASortingLink()
    {
        $links = $this->headerLinks($this->renderWorkers(new WorkerCriteria(), []));

        $expected = [
            'ID' => 'id',
            'Status' => 'status',
            'Running for' => 'started',
            'Running job' => 'job',
            'P' => 'processed',
            'C' => 'cancelled',
            'F' => 'failed',
            'Interval' => 'interval',
            'Timeout' => 'timeout',
            'Memory (limit)' => 'memory',
        ];

        foreach ($expected as $label => $field) {
            $this->assertArrayHasKey($label, $links, $label . ' is not a sorting link');
            $this->assertStringContainsString('sort=' . $field, $links[$label]);
            $this->assertStringContainsString('andaris_resque_web_ui_workers', $links[$label]);
        }
    }

    public function testEveryQueueColumnIsASortingLink()
    {
        $links = $this->headerLinks($this->renderQueues(new QueueCriteria(), []));

        $expected = [
            'Name' => 'name',
            'Queued' => 'queued',
            'Delayed' => 'delayed',
            'Processed' => 'processed',
            'Cancelled' => 'cancelled',
            'Failed' => 'failed',
            'Total' => 'total',
        ];

        foreach ($expected as $label => $field) {
            $this->assertArrayHasKey($label, $links, $label . ' is not a sorting link');
            $this->assertStringContainsString('sort=' . $field, $links[$label]);
            $this->assertStringContainsString('andaris_resque_web_ui_queues', $links[$label]);
        }
    }

    /**
     * The column in use links to the opposite direction, every other one to
     * ascending.
     */
    public function testTheWorkerColumnInUseTogglesTheDirection()
    {
        $links = $this->headerLinks($this->renderWorkers(new WorkerCriteria('id', 'asc'), []));

        $this->assertStringContainsString('direction=desc', $links['ID']);
        $this->assertStringContainsString('direction=asc', $links['Status']);
    }

    public function testTheQueueColumnInUseCarriesTheIndicator()
    {
        $output = $this->renderQueues(new QueueCriteria('total', 'desc'), []);

        $this->assertRegExp('#Total\s*<span class="caret"></span>#', $output);
    }

    /**
     * The attributes of a column are fixed markup handed to the macro, so they
     * have to reach the tag as markup rather than as text.
     */
    public function testTheAttributesOfAColumnReachTheHeaderUnescaped()
    {
        $output = $this->renderWorkers(new WorkerCriteria(), []);

        $this->assertStringContainsString('<th title="Processed" style="width: 4em">', $output);
        $this->assertStringNotContainsString('&quot;', $output);
    }

    public function testTheWorkerColumnInUseCarriesTheIndicator()
    {
        $output = $this->renderWorkers(new WorkerCriteria('id', 'asc'), []);

        $this->assertStringContainsString('caret-up', $output);
    }

    public function testTheEmptyListsSaySo()
    {
        $this->assertStringContainsString(
            'No running workers found.',
            $this->renderWorkers(new WorkerCriteria(), [])
        );
        $this->assertStringContainsString('No queues found.', $this->renderQueues(new QueueCriteria(), []));
    }

    public function testTheQueueListShowsTheCounters()
    {
        $output = $this->renderQueues(new QueueCriteria(), [new Queue('emails', 1, 2, 3, 4, 5)]);

        $this->assertStringContainsString('emails', $output);
        $this->assertStringContainsString('15', $output, 'the total is not rendered');
    }

    private function renderWorkers(WorkerCriteria $criteria, array $workers)
    {
        return $this->renderTemplate('Worker/index.html.twig', [
            'workers' => $workers,
            'criteria' => $criteria,
        ]);
    }

    private function renderQueues(QueueCriteria $criteria, array $queues)
    {
        return $this->renderTemplate('Queue/index.html.twig', [
            'queues' => $queues,
            'criteria' => $criteria,
        ]);
    }

    private function createWorker($id)
    {
        return new Worker(
            $id,
            ResqueWorker::STATUS_RUNNING,
            1500000000,
            null,
            0,
            7,
            2,
            1,
            5,
            60,
            1048576,
            128
        );
    }
}
