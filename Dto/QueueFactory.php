<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

use Predis\CommunicationException;

use Andaris\ResqueWebUiBundle\Adapter\QueueAdapter;
use Andaris\ResqueWebUiBundle\Adapter\RedisAdapter;
use Andaris\ResqueWebUiBundle\Exception\RedisUnavailableException;

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
     * Creates an array of all queues, ordered by the given criteria.
     *
     * @param QueueCriteria $criteria
     *
     * @return Queue[]
     */
    public function createAll(?QueueCriteria $criteria = null)
    {
        /**
         * @var Queue[]
         */
        $queues = [];

        try {
            $rawQueues = $this->redisAdapter->instance()->smembers('queues');
        } catch (CommunicationException $failure) {
            throw RedisUnavailableException::fromCommunicationFailure($failure);
        }

        foreach ($rawQueues as $queue) {
            try {
                $stats = $this->redisAdapter->instance()->hgetall($this->queueAdapter->redisKey($queue, 'stats'));
            } catch (CommunicationException $failure) {
                throw RedisUnavailableException::fromCommunicationFailure($failure);
            }

            $queues[] = new Queue(
                $queue,
                isset($stats['queued']) ? (int) $stats['queued'] : 0,
                isset($stats['delayed']) ? (int) $stats['delayed'] : 0,
                isset($stats['processed']) ? (int) $stats['processed'] : 0,
                isset($stats['cancelled']) ? (int) $stats['cancelled'] : 0,
                isset($stats['failed']) ? (int) $stats['failed'] : 0
            );
        }

        $criteria = $criteria ?: new QueueCriteria();

        return $criteria->sort($queues);
    }
}
