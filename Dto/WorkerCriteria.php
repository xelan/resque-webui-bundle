<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

/**
 * The ordering of the worker list.
 *
 * @internal the lists of the bundle are rendered through this; it is not an
 *           extension point and may change without notice
 */
class WorkerCriteria extends SortCriteria
{
    const DEFAULT_FIELD = 'id';
    const DEFAULT_DIRECTION = self::DIRECTION_ASCENDING;

    const IDENTITY_GETTER = 'getId';

    /**
     * The worker fields that can be sorted on, mapped to their getter.
     */
    const FIELDS = [
        'id' => 'getId',
        'status' => 'getStatus',
        'started' => 'getStarted',
        'job' => 'getCurrentJobId',
        'processed' => 'getJobsProcessed',
        'cancelled' => 'getJobsCancelled',
        'failed' => 'getJobsFailed',
        'interval' => 'getInterval',
        'timeout' => 'getTimeout',
        'memory' => 'getMemory',
    ];

    /**
     * The fields whose values are compared as numbers rather than as text.
     */
    const NUMERIC_FIELDS = [
        'status',
        'started',
        'processed',
        'cancelled',
        'failed',
        'interval',
        'timeout',
        'memory',
    ];

    /**
     * The start time sits behind the "Running for" column, which shows how long
     * ago it was: the earlier a worker started, the longer it has been running.
     */
    const INVERTED_FIELDS = ['started'];
}
