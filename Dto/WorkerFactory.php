<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;

class WorkerFactory
{
    /**
     * @var WorkerAdapter
     */
    private $workerAdapter;

    /**
     * Constructor.
     *
     * @param WorkerAdapter $workerAdapter
     */
    public function __construct(WorkerAdapter $workerAdapter)
    {
        $this->workerAdapter = $workerAdapter;
    }

    /**
     * Creates an array of all workers, ordered by the given criteria.
     *
     * @param WorkerCriteria $criteria
     *
     * @return Worker[]
     */
    public function createAll(?WorkerCriteria $criteria = null)
    {
        /**
         * @var Worker[]
         */
        $workers = [];

        $rawWorkers = $this->workerAdapter->allWorkers();

        foreach ($rawWorkers as $worker) {
            $packet = $worker->getPacket();

            $workers[] = new Worker(
                (string) $worker,
                $packet['status'],
                $packet['started'],
                !empty($packet['job_id']) ? $packet['job_id'] : null,
                $packet['job_started'],
                $packet['processed'],
                $packet['cancelled'],
                $packet['failed'],
                $packet['interval'],
                $packet['timeout'],
                $packet['memory'],
                $packet['memory_limit']
            );
        }

        $criteria = $criteria ?: new WorkerCriteria();

        return $criteria->sort($workers);
    }
}
