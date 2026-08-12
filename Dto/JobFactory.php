<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

use Predis\CommunicationException;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;
use Andaris\ResqueWebUiBundle\Adapter\RedisAdapter;
use Andaris\ResqueWebUiBundle\Exception\RedisUnavailableException;

class JobFactory
{
    /**
     * @var JobAdapter
     */
    private $jobAdapter;

    /**
     * @var RedisAdapter
     */
    private $redisAdapter;

    /**
     * Constructor.
     *
     * @param JobAdapter   $jobAdapter
     * @param RedisAdapter $redisAdapter
     */
    public function __construct(JobAdapter $jobAdapter, RedisAdapter $redisAdapter)
    {
        $this->jobAdapter = $jobAdapter;
        $this->redisAdapter = $redisAdapter;
    }


    /**
     * Creates an array of all jobs, ordered by the given criteria.
     *
     * Redis returns the keys in no particular order, so the list is always
     * sorted, with or without criteria.
     *
     * @param JobCriteria $criteria
     *
     * @return Job[]
     */
    public function createAll(?JobCriteria $criteria = null)
    {
        /**
         * @var Job[]
         */
        $jobs = [];

        try {
            $jobKeys = $this->redisAdapter->instance()->keys('job:*');
        } catch (CommunicationException $failure) {
            throw RedisUnavailableException::fromCommunicationFailure($failure);
        }

        foreach ($jobKeys as $key) {
            $keyArray = explode(':', $key);
            $id = array_pop($keyArray);

            $job = $this->createById($id);

            // the job may have expired between listing the keys and reading it
            if ($job !== null) {
                $jobs[] = $job;
            }
        }

        $criteria = $criteria ?: new JobCriteria();

        return $criteria->sort($jobs);
    }


    public function createById($id)
    {
        try {
            $data = $this->redisAdapter->instance()->hgetall('job:' . $id);
        } catch (CommunicationException $failure) {
            throw RedisUnavailableException::fromCommunicationFailure($failure);
        }

        if (!$data) {
            return null;
        }

        return new Job(
            $data['id'],
            $data['status'],
            $data['queue'],
            empty($data['worker']) ? null : $data['worker'],
            empty($data['payload']) ? null : $data['payload'],
            empty($data['exception']) ? null : $data['exception'],
            empty($data['created']) ? null : $data['created'],
            empty($data['started']) ? null : $data['started'],
            empty($data['updated']) ? null : $data['updated'],
            empty($data['finished']) ? null : $data['finished']
        );
    }
}
