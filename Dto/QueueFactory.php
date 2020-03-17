<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

use Andaris\ResqueWebUiBundle\Adapter\QueueAdapter;
use Andaris\ResqueWebUiBundle\Adapter\RedisAdapter;

class QueueFactory
{
    /**
     * @var QueueAdapter
     */
    private $queueAdapter;

    /**
     * @var RedisAdapter
     */
    private $redisAdapter;

    /**
     * Constructor.
     *
     * @param QueueAdapter $queueAdapter
     * @param RedisAdapter $redisAdapter
     */
    public function __construct(QueueAdapter $queueAdapter, RedisAdapter $redisAdapter)
    {
        $this->queueAdapter = $queueAdapter;
        $this->redisAdapter = $redisAdapter;
    }

    /**
     * Creates an array of all queues.
     *
     * @return Queue[]
     */
    public function createAll()
    {
        /**
         * @var Queue[]
         */
        $queues = [];

        $rawQueues = $this->redisAdapter->instance()->smembers('queues');

        foreach ($rawQueues as $queue) {
            $stats = $this->redisAdapter->instance()->hgetall($this->queueAdapter->redisKey($queue, 'stats'));

            $queues[] = new Queue(
                $queue,
                isset($stats['queued']) ? (int) $stats['queued'] : 0,
                isset($stats['delayed']) ? (int) $stats['delayed'] : 0,
                isset($stats['processed']) ? (int) $stats['processed'] : 0,
                isset($stats['cancelled']) ? (int) $stats['cancelled'] : 0,
                isset($stats['failed']) ? (int) $stats['failed'] : 0
            );
        }

        return $queues;
    }
}
