<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

use Resque\Worker;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;

class MetricsController extends AbstractController
{
    const LF = "\n";

    const PROMETHEUS_FORMAT_VERSION = '0.0.4';

    /**
     * @var QueueFactory
     */
    private $queueFactory;

    /**
     * @var WorkerFactory
     */
    private $workerFactory;

    /**
     * Constructor.
     *
     * @param ResqueConfigurator $configurator
     * @param QueueFactory       $queueFactory
     * @param WorkerFactory      $workerFactory
     */
    public function __construct(
        ResqueConfigurator $configurator,
        QueueFactory $queueFactory,
        WorkerFactory $workerFactory
    ) {
        parent::__construct(null, $configurator);

        $this->queueFactory = $queueFactory;
        $this->workerFactory = $workerFactory;
    }

    /**
     * Exports the queue and worker statistics using the prometheus exposition format.
     *
     * @see https://prometheus.io/docs/instrumenting/exposition_formats/
     *
     * @return Response
     */
    public function exportPrometheusAction()
    {
        $queues = $this->queueFactory->createAll();
        $workers = $this->workerFactory->createAll();

        $output = $this->buildPrometheusQueueStats($queues) . self::LF . $this->buildPrometheusWorkerStats($workers);

        return new Response(
            $output,
            Response::HTTP_OK,
            ['Content-Type' => sprintf('text/plain; version=%s', self::PROMETHEUS_FORMAT_VERSION)]
        );
    }

    /**
     * Builds the queue statistics in prometheus exposition format.
     *
     * @param Queue[] $queues
     *
     * @return string
     */
    private function buildPrometheusQueueStats(array $queues)
    {
        $lf = self::LF;

        $output = '# queue related:' . $lf;

        $output .= '# HELP queue_queued queued jobs' . $lf . '# TYPE queue_queued gauge' . $lf;
        foreach ($queues as $queue) {
            $output .= sprintf('queue_queued{queue="%s"} %d', $queue->getName(), $queue->getJobsQueued()) . $lf;
        }
        $output .= '# HELP queue_delayed delayed jobs' . $lf . '# TYPE queue_delayed gauge' . $lf;
        foreach ($queues as $queue) {
            $output .= sprintf('queue_delayed{queue="%s"} %d', $queue->getName(), $queue->getJobsDelayed()) . $lf;
        }

        $output .= '# HELP queue_processed processed jobs' . $lf . '# TYPE queue_processed counter' . $lf;
        foreach ($queues as $queue) {
            $output .= sprintf('queue_processed{queue="%s"} %d', $queue->getName(), $queue->getJobsProcessed()) . $lf;
        }
        $output .= '# HELP queue_cancelled cancelled jobs' . $lf . '# TYPE queue_cancelled counter' . $lf;
        foreach ($queues as $queue) {
            $output .= sprintf('queue_cancelled{queue="%s"} %d', $queue->getName(), $queue->getJobsCancelled()) . $lf;
        }
        $output .= '# HELP queue_failed failed jobs' . $lf . '# TYPE queue_failed counter' . $lf;
        foreach ($queues as $queue) {
            $output .= sprintf('queue_failed{queue="%s"} %d', $queue->getName(), $queue->getJobsFailed()) . $lf;
        }

        return $output;
    }

    /**
     * Builds the worker statistics in prometheus exposition format.
     *
     * @param Worker[] $workers
     *
     * @return string
     */
    private function buildPrometheusWorkerStats(array $workers)
    {
        $lf = self::LF;

        $output = '# worker related:' . $lf;

        $output .= '# HELP worker_running number of workers' . $lf . '# TYPE worker_running gauge' . $lf;

        $running = 0;
        foreach ($workers as $worker) {
            if (Worker::STATUS_RUNNING === (int) $worker->getStatus()) {
                ++$running;
            }
        }
        $output .= sprintf('worker_running %d', $running) . $lf;

        $output .= '# HELP worker_processed processed jobs' . $lf . '# TYPE worker_processed counter' . $lf;
        foreach ($workers as $worker) {
            $output .= sprintf('worker_processed{id="%s"} %d', $worker->getId(), $worker->getJobsProcessed()) . $lf;
        }
        $output .= '# HELP worker_cancelled cancelled jobs' . $lf . '# TYPE worker_cancelled counter' . $lf;
        foreach ($workers as $worker) {
            $output .= sprintf('worker_cancelled{id="%s"} %d', $worker->getId(), $worker->getJobsCancelled()) . $lf;
        }
        $output .= '# HELP worker_failed failed jobs' . $lf . '# TYPE worker_failed counter' . $lf;
        foreach ($workers as $worker) {
            $output .= sprintf('worker_failed{id="%s"} %d', $worker->getId(), $worker->getJobsFailed()) . $lf;
        }
        $output .= '# HELP worker_mem memory usage' . $lf . '# TYPE worker_mem gauge' . $lf;
        foreach ($workers as $worker) {
            $output .= sprintf('worker_mem{id="%s"} %d', $worker->getId(), $worker->getMemory()) . $lf;
        }

        return $output;
    }
}
