<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Resque\Worker;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;

class MetricsController extends AbstractController
{
    const LF = "\n";

    const PROMETHEUS_FORMAT_VERSION = '0.0.4';
    const PROMETHEUS_USER_AGENT = 'Prometheus/';

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
     * Exports the queue and worker statistics in the format detected via the request data.
     * For unsupported requests, a BadRequestHttpException is thrown.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws BadRequestHttpException
     */
    public function exportAction(Request $request)
    {
        if ($this->isPrometheusRequest($request)) {
            return $this->exportPrometheusAction();
        }

        throw new BadRequestHttpException('Unsupported request!');
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
     * Returns whether the request is coming from prometheus (based on the user agent string).
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isPrometheusRequest(Request $request)
    {
        return false !== stripos($request->headers->get('User-Agent'), self::PROMETHEUS_USER_AGENT);
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

        $o = '# queue related:' . $lf;

        $o .= '# HELP resque_queue_queued queued jobs' . $lf . '# TYPE resque_queue_queued gauge' . $lf;
        foreach ($queues as $queue) {
            $o .= sprintf('resque_queue_queued{queue="%s"} %d', $queue->getName(), $queue->getJobsQueued()) . $lf;
        }
        $o .= '# HELP resque_queue_delayed delayed jobs' . $lf . '# TYPE resque_queue_delayed gauge' . $lf;
        foreach ($queues as $queue) {
            $o .= sprintf('resque_queue_delayed{queue="%s"} %d', $queue->getName(), $queue->getJobsDelayed()) . $lf;
        }

        $o .= '# HELP resque_queue_processed processed jobs' . $lf . '# TYPE resque_queue_processed counter' . $lf;
        foreach ($queues as $queue) {
            $o .= sprintf('resque_queue_processed{queue="%s"} %d', $queue->getName(), $queue->getJobsProcessed()) . $lf;
        }
        $o .= '# HELP resque_queue_cancelled cancelled jobs' . $lf . '# TYPE resque_queue_cancelled counter' . $lf;
        foreach ($queues as $queue) {
            $o .= sprintf('resque_queue_cancelled{queue="%s"} %d', $queue->getName(), $queue->getJobsCancelled()) . $lf;
        }
        $o .= '# HELP resque_queue_failed failed jobs' . $lf . '# TYPE resque_queue_failed counter' . $lf;
        foreach ($queues as $queue) {
            $o .= sprintf('resque_queue_failed{queue="%s"} %d', $queue->getName(), $queue->getJobsFailed()) . $lf;
        }

        return $o;
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

        $o = '# worker related:' . $lf;

        list($new, $running, $paused) = $this->getWorkerStatusStats($workers);

        $o .= '# HELP resque_worker_new number of new workers' . $lf . '# TYPE resque_worker_new gauge' . $lf;
        $o .= sprintf('resque_worker_new %d', $new) . $lf;

        $o .= '# HELP resque_worker_running number of workers' . $lf . '# TYPE resque_worker_running gauge' . $lf;
        $o .= sprintf('resque_worker_running %d', $running) . $lf;

        $o .= '# HELP resque_worker_paused number of paused workers' . $lf . '# TYPE resque_worker_paused gauge' . $lf;
        $o .= sprintf('resque_worker_paused %d', $paused) . $lf;

        $o .= '# HELP resque_worker_processed processed jobs' . $lf . '# TYPE resque_worker_processed counter' . $lf;
        foreach ($workers as $worker) {
            $o .= sprintf('resque_worker_processed{id="%s"} %d', $worker->getId(), $worker->getJobsProcessed()) . $lf;
        }
        $o .= '# HELP resque_worker_cancelled cancelled jobs' . $lf . '# TYPE resque_worker_cancelled counter' . $lf;
        foreach ($workers as $worker) {
            $o .= sprintf('resque_worker_cancelled{id="%s"} %d', $worker->getId(), $worker->getJobsCancelled()) . $lf;
        }
        $o .= '# HELP resque_worker_failed failed jobs' . $lf . '# TYPE resque_worker_failed counter' . $lf;
        foreach ($workers as $worker) {
            $o .= sprintf('resque_worker_failed{id="%s"} %d', $worker->getId(), $worker->getJobsFailed()) . $lf;
        }
        $o .= '# HELP resque_worker_mem memory usage' . $lf . '# TYPE resque_worker_mem gauge' . $lf;
        foreach ($workers as $worker) {
            $o .= sprintf('resque_worker_mem{id="%s"} %d', $worker->getId(), $worker->getMemory()) . $lf;
        }

        return $o;
    }

    /**
     * Returns the number of new, running and paused workers as an array.
     *
     * @param Worker[] $workers
     *
     * @return int[]
     */
    private function getWorkerStatusStats(array $workers)
    {
        $new = 0;
        $running = 0;
        $paused = 0;

        foreach ($workers as $worker) {
            switch ((int) $worker->getStatus()) {
                case Worker::STATUS_NEW:
                    ++$new;
                    break;
                case Worker::STATUS_RUNNING:
                    ++$running;
                    break;
                case Worker::STATUS_PAUSED:
                    ++$paused;
                    break;
            }
        }

        return [$new, $running, $paused];
    }
}
