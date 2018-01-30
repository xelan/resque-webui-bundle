<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2018 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

class Worker
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $status;

    /**
     * @var int
     */
    private $started;

    /**
     * @var string
     */
    private $currentJobId;

    /**
     * @var int
     */
    private $currentJobStarted;

    /**
     * @var int
     */
    private $jobsProcessed;

    /**
     * @var int
     */
    private $jobsCancelled;

    /**
     * @var int
     */
    private $jobsFailed;

    /**
     * @var int
     */
    private $interval;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var int
     */
    private $memory;

    /**
     * @var int Memory limit in Megabytes
     */
    private $memoryLimit;

    /**
     * Constructor.
     *
     * @param string $id
     * @param int    $status
     * @param int    $started
     * @param string $currentJobId
     * @param int    $currentJobStarted
     * @param int    $jobsProcessed
     * @param int    $jobsCancelled
     * @param int    $jobsFailed
     * @param int    $interval
     * @param int    $timeout
     * @param int    $memory
     * @param int    $memoryLimit
     */
    public function __construct(
        $id,
        $status,
        $started,
        $currentJobId,
        $currentJobStarted,
        $jobsProcessed,
        $jobsCancelled,
        $jobsFailed,
        $interval,
        $timeout,
        $memory,
        $memoryLimit
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->started = $started;
        $this->currentJobId = $currentJobId;
        $this->currentJobStarted = $currentJobStarted;
        $this->jobsProcessed = $jobsProcessed;
        $this->jobsCancelled = $jobsCancelled;
        $this->jobsFailed = $jobsFailed;
        $this->interval = $interval;
        $this->timeout = $timeout;
        $this->memory = $memory;
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * Returns the worker ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the worker status (as one of the STATUS_* constants).
     *
     * @see Resque\Worker
     *
     * @return int One of the STATUS_* constants
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns when the worker was started.
     *
     * @return int Unix timestamp
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Returns the ID of the job which is currently processed.
     * If there is no current job, null is returned.
     *
     * @return string
     */
    public function getCurrentJobId()
    {
        return $this->currentJobId;
    }

    /**
     * Returns when the current job was started.
     *
     * @return int Unix timestamp
     */
    public function getCurrentJobStarted()
    {
        return $this->currentJobStarted;
    }

    /**
     * Returns the number of processed jobs of the worker.
     *
     * @return int
     */
    public function getJobsProcessed()
    {
        return $this->jobsProcessed;
    }

    /**
     * Returns the number of cancelled jobs of the worker.
     *
     * @return int
     */
    public function getJobsCancelled()
    {
        return $this->jobsCancelled;
    }

    /**
     * Returns the number of failed jobs of the worker.
     *
     * @return int
     */
    public function getJobsFailed()
    {
        return $this->jobsFailed;
    }

    /**
     * Returns the interval the worker has configured (in seconds).
     *
     * @return int
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Returns the timeout the worker has configured (in seconds).
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Returns the memory usage status of the worker.
     *
     * @return int Used memory in bytes
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * Returns the memory limit of the worker.
     *
     * @return int Memory limit in MB
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }
}
