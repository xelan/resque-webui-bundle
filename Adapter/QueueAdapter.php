<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2018 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Adapter;

use Resque\Queue;

/**
 * Adapter class which provides the static methods of Resque\Queue as non-static methods.
 */
class QueueAdapter
{
    /**
     * Returns the Queue Redis key.
     *
     * @param  Queue  $queue  the worker to get the key for
     * @param  string $suffix to be appended to key
     *
     * @return string
     */
    public function redisKey($queue = null, $suffix = null)
    {
        return Queue::redisKey($queue, $suffix);
    }
}
