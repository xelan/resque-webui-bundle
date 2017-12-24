<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

class Queue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $jobsQueued;

    /**
     * @var int
     */
    private $jobsDelayed;

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
     * Constructor.
     *
     * @param string $name
     * @param int    $jobsQueued
     * @param int    $jobsDelayed
     * @param int    $jobsProcessed
     * @param int    $jobsCancelled
     * @param int    $jobsFailed
     */
    public function __construct(
        $name,
        $jobsQueued,
        $jobsDelayed,
        $jobsProcessed,
        $jobsCancelled,
        $jobsFailed
    ) {
        $this->name = $name;
        $this->jobsQueued = $jobsQueued;
        $this->jobsDelayed = $jobsDelayed;
        $this->jobsProcessed = $jobsProcessed;
        $this->jobsCancelled = $jobsCancelled;
        $this->jobsFailed = $jobsFailed;
    }

    /**
     * Returns the queue name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the number of queued jobs in the Queue.
     *
     * @return int
     */
    public function getJobsQueued()
    {
        return $this->jobsQueued;
    }

    /**
     * Returns the number of delayed jobs in the Queue.
     *
     * @return int
     */
    public function getJobsDelayed()
    {
        return $this->jobsDelayed;
    }

    /**
     * Returns the number of processed jobs in the Queue.
     *
     * @return int
     */
    public function getJobsProcessed()
    {
        return $this->jobsProcessed;
    }

    /**
     * Returns the number of cancelled jobs in the Queue.
     *
     * @return int
     */
    public function getJobsCancelled()
    {
        return $this->jobsCancelled;
    }

    /**
     * Returns the number of failed jobs in the Queue.
     *
     * @return int
     */
    public function getJobsFailed()
    {
        return $this->jobsFailed;
    }

    /**
     * Returns the number of total jobs in the Queue.
     *
     * @return int
     */
    public function getJobsTotal()
    {
        $jobNumbers = [
            (int) $this->jobsQueued,
            (int) $this->jobsDelayed,
            (int) $this->jobsProcessed,
            (int) $this->jobsCancelled,
            (int) $this->jobsFailed,
        ];

        return array_sum($jobNumbers);
    }
}
