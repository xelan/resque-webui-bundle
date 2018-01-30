<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2018 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Adapter;

use InvalidArgumentException;
use Resque\Job;

/**
 * Adapter class which provides the static methods of Resque\Job as non-static methods.
 */
class JobAdapter
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
        switch ($status) {
            case Job::STATUS_WAITING:
                return 'Waiting';
            case Job::STATUS_DELAYED:
                return 'Delayed';
            case Job::STATUS_RUNNING:
                return 'Running';
            case Job::STATUS_COMPLETE:
                return 'Complete';
            case Job::STATUS_CANCELLED:
                return 'Cancelled';
            case Job::STATUS_FAILED:
                return 'Failed';
            default:
                throw new InvalidArgumentException(sprintf('Invalid status "%s"!'), $status);
        }
    }

    /**
     * Returns the Job Redis key.
     *
     * @param  Job    $job the job to get the key for
     * @param  string $suffix to be appended to key
     *
     * @return string
     */
    public static function redisKey($job, $suffix = null)
    {
        return Job::redisKey($job, $suffix);
    }
}
