<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;
use Andaris\ResqueWebUiBundle\Adapter\RedisAdapter;

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
     * Creates an array of all jobs.
     *
     * @return Job[]
     */
    public function createAll()
    {
        /**
         * @var Job[]
         */
        $jobs = [];

        $jobKeys = $this->redisAdapter->instance()->keys('job:*');

        foreach ($jobKeys as $key) {
            $keyArray = explode(':', $key);
            $id = array_pop($keyArray);

            $jobs[] = $this->createById($id);
        }

        return $jobs;
    }

    public function createById($id)
    {
        $data = $this->redisAdapter->instance()->hgetall($id);

        if (!$data = $this->redisAdapter->instance()->hgetall('job:' . $id)) {
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
