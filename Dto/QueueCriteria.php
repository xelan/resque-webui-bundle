<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

/**
 * The ordering of the queue list.
 *
 * @internal the lists of the bundle are rendered through this; it is not an
 *           extension point and may change without notice
 */
class QueueCriteria extends SortCriteria
{
    const DEFAULT_FIELD = 'name';
    const DEFAULT_DIRECTION = self::DIRECTION_ASCENDING;

    const IDENTITY_GETTER = 'getName';

    /**
     * The queue fields that can be sorted on, mapped to their getter.
     */
    const FIELDS = [
        'name' => 'getName',
        'queued' => 'getJobsQueued',
        'delayed' => 'getJobsDelayed',
        'processed' => 'getJobsProcessed',
        'cancelled' => 'getJobsCancelled',
        'failed' => 'getJobsFailed',
        'total' => 'getJobsTotal',
        'rate' => 'getFailureRate',
    ];

    /**
     * The fields whose values are compared as numbers rather than as text.
     */
    const NUMERIC_FIELDS = [
        'queued',
        'delayed',
        'processed',
        'cancelled',
        'failed',
        'total',
        'rate',
    ];
}
