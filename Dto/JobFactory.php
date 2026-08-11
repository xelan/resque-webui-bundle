<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
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
     * Creates an array of all jobs, ordered by the given criteria.
     *
     * Redis returns the keys in no particular order, so the list is always
     * sorted, with or without criteria.
     *
     * @param JobCriteria $criteria
     *
     * @return Job[]
     */
    public function createAll(JobCriteria $criteria = null)
    {
        /**
         * @var Job[]
         */
        $jobs = [];

        $jobKeys = $this->redisAdapter->instance()->keys('job:*');

        foreach ($jobKeys as $key) {
            $keyArray = explode(':', $key);
            $id = array_pop($keyArray);

            $job = $this->createById($id);

            // the job may have expired between listing the keys and reading it
            if ($job !== null) {
                $jobs[] = $job;
            }
        }

        return $this->sort($jobs, $criteria ?: new JobCriteria());
    }

    /**
     * Orders the jobs by the field of the criteria.
     *
     * Jobs without a value for that field are always put last, no matter which
     * direction is asked for; a job that never started is of no interest at the
     * top of the list. Jobs that compare equal are ordered by their id, so that
     * the result does not depend on the sort implementation of the PHP version
     * in use.
     *
     * @param Job[]       $jobs
     * @param JobCriteria $criteria
     *
     * @return Job[]
     */
    private function sort(array $jobs, JobCriteria $criteria)
    {
        $getter = $criteria->getFieldGetter();
        $numeric = $criteria->isNumericField();
        $descending = $criteria->isDescending();

        usort($jobs, function (Job $left, Job $right) use ($getter, $numeric, $descending) {
            $leftValue = $left->{$getter}();
            $rightValue = $right->{$getter}();

            $leftIsEmpty = ($leftValue === null || $leftValue === '');
            $rightIsEmpty = ($rightValue === null || $rightValue === '');

            if ($leftIsEmpty || $rightIsEmpty) {
                if ($leftIsEmpty && $rightIsEmpty) {
                    return strcmp($left->getId(), $right->getId());
                }

                return $leftIsEmpty ? 1 : -1;
            }

            $result = $numeric
                ? ((int) $leftValue <=> (int) $rightValue)
                : strcmp((string) $leftValue, (string) $rightValue);

            if ($result === 0) {
                return strcmp($left->getId(), $right->getId());
            }

            return $descending ? -$result : $result;
        });

        return $jobs;
    }

    public function createById($id)
    {
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
