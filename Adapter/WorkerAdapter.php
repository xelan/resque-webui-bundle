<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Adapter;

use InvalidArgumentException;
use Resque\Worker;

/**
 * Adapter class which provides the static methods of Resque\Worker as non-static methods.
 */
class WorkerAdapter
{
    /**
     * Returns the status text for a status code.
     *
     * @param int $status
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getStatusText($status)
    {
        if (!array_key_exists($status, Worker::$statusText)) {
            throw new InvalidArgumentException(sprintf('Invalid status "%s"!'), $status);
        }

        return Worker::$statusText[$status];
    }

    /**
     * Returns the Worker Redis key.
     *
     * @param Worker $worker the worker to get the key for
     * @param string $suffix to be appended to key
     *
     * @return string
     */
    public function redisKey($worker = null, $suffix = null)
    {
        return Worker::redisKey($worker, $suffix);
    }

    /**
     * Returns a worker from its ID.
     *
     * @param  string $id     Worker id
     * @param  Logger $logger Logger for the worker to use
     *
     * @return Worker
     */
    public function fromId($id, Logger $logger = null)
    {
        return Worker::fromId($id, $logger);
    }

    /**
     * Returns all known workers.
     *
     * @return Worker[]
     */
    public function allWorkers(Logger $logger = null)
    {
        return Worker::allWorkers($logger);
    }

    /**
     * Returns host worker by id.
     *
     * @param  string $id     Worker id
     * @param  string $host   Hostname
     * @param  Logger $logger Logger
     *
     * @return Worker|false
     */
    public function hostWorker($id, $host = null, Logger $logger = null)
    {
        return Worker::hostWorker($id, $host, $logger);
    }

    /**
     * Returns all known workers on a host.
     *
     * @param  string $host   Hostname
     * @param  Logger $logger Logger
     *
     * @return Worker[]
     */
    public function hostWorkers($host = null, Logger $logger = null)
    {
        return Worker::hostWorkers($host, $logger);
    }
}
