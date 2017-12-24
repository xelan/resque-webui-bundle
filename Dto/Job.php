<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

class Job
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
     * @var string
     */
    private $queue;

    /**
     * @var string
     */
    private $worker;

    /**
     * @var string
     */
    private $payload;

    /**
     * @var string
     */
    private $exception;

    /**
     * @var int
     */
    private $created;

    /**
     * @var int
     */
    private $started;

    /**
     * @var int
     */
    private $updated;

    /**
     * @var int
     */
    private $finished;

    /**
     * Constructor.
     *
     * @param string $id
     * @param int    $status
     * @param string $queue
     * @param string $worker
     * @param string $payload
     * @param string $exception
     * @param int    $created
     * @param int    $started
     * @param int    $updated
     * @param int    $finished
     */
    public function __construct(
        $id,
        $status,
        $queue,
        $worker,
        $payload,
        $exception,
        $created,
        $started,
        $updated,
        $finished
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->queue = $queue;
        $this->worker = $worker;
        $this->payload = $payload;
        $this->exception = $exception;
        $this->created = $created;
        $this->started = $started;
        $this->updated = $updated;
        $this->finished = $finished;
    }

    /**
     * Returns the job ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the job status (as one of the STATUS_* constants).
     *
     * @see Resque\Job
     *
     * @return int One of the STATUS_* constants
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the queue name of the job.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Returns the worker ID of the job.
     * If no worker took the job, it is null.
     *
     * @return string
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * Returns the job payload (JSON data).
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Returns an uncaught job exception if applicable (JSON data).
     *
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Returns when the job was created.
     *
     * @return int Unix timestamp
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Returns when the job was started.
     *
     * @return int Unix timestamp
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Returns when the job was updated.
     *
     * @return int Unix timestamp
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Returns when the job was finished.
     *
     * @return int Unix timestamp
     */
    public function getFinished()
    {
        return $this->finished;
    }
}
