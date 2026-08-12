<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

use Resque\Job as ResqueJob;
use Symfony\Component\HttpFoundation\Request;

/**
 * The ordering and the status filter of the job list.
 *
 * The values end up selecting a getter and are taken from the query string, so
 * everything is checked against a whitelist. An unknown value falls back to the
 * default instead of being rejected, which keeps a stale bookmark working.
 *
 * @internal the lists of the bundle are rendered through this; it is not an
 *           extension point and may change without notice
 */
class JobCriteria extends SortCriteria
{
    const DEFAULT_FIELD = 'created';
    const DEFAULT_DIRECTION = self::DIRECTION_DESCENDING;

    /**
     * The job fields that can be sorted on, mapped to their getter.
     */
    const FIELDS = [
        'id' => 'getId',
        'status' => 'getStatus',
        'queue' => 'getQueue',
        'worker' => 'getWorker',
        'created' => 'getCreated',
        'started' => 'getStarted',
        'updated' => 'getUpdated',
        'finished' => 'getFinished',
    ];

    /**
     * The fields whose values are compared as numbers rather than as text.
     */
    const NUMERIC_FIELDS = ['status', 'created', 'started', 'updated', 'finished'];

    const IDENTITY_GETTER = 'getId';

    /**
     * The selectable statuses, in the order a job passes through them.
     */
    const STATUSES = [
        ResqueJob::STATUS_WAITING,
        ResqueJob::STATUS_DELAYED,
        ResqueJob::STATUS_RUNNING,
        ResqueJob::STATUS_COMPLETE,
        ResqueJob::STATUS_CANCELLED,
        ResqueJob::STATUS_FAILED,
    ];

    /**
     * @var int|null
     */
    private $status;

    /**
     * Constructor.
     *
     * @param string   $field     one of the FIELDS keys
     * @param string   $direction one of the DIRECTION_* constants
     * @param int|null $status    one of the STATUSES, null for all jobs
     */
    public function __construct($field = null, $direction = null, $status = null)
    {
        parent::__construct($field, $direction);

        // casting an array to int yields 1, which is a status of its own
        $isKnownStatus = is_scalar($status) && in_array((int) $status, self::STATUSES, true);

        $this->status = $isKnownStatus ? (int) $status : null;
    }

    /**
     * Reads the criteria from the query string of a request.
     *
     * @param Request $request
     *
     * @return JobCriteria
     */
    public static function fromRequest(Request $request)
    {
        return new self(
            $request->query->get('sort'),
            $request->query->get('direction'),
            $request->query->get('status')
        );
    }

    /**
     * Returns the status to filter on, or null when all jobs are shown.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }








    /**
     * Returns whether a job passes the status filter.
     *
     * @param Job $job
     *
     * @return bool
     */
    public function matches(Job $job)
    {
        return $this->status === null || (int) $job->getStatus() === $this->status;
    }
}
