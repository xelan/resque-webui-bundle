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
 */
class JobCriteria
{
    const DIRECTION_ASCENDING = 'asc';
    const DIRECTION_DESCENDING = 'desc';

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
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $direction;

    /**
     * Constructor.
     *
     * @param int|null $status    one of the STATUSES, null for all jobs
     * @param string   $field     one of the FIELDS keys
     * @param string   $direction one of the DIRECTION_* constants
     */
    public function __construct($status = null, $field = self::DEFAULT_FIELD, $direction = self::DEFAULT_DIRECTION)
    {
        $this->status = in_array((int) $status, self::STATUSES, true) ? (int) $status : null;
        $this->field = array_key_exists($field, self::FIELDS) ? $field : self::DEFAULT_FIELD;
        $this->direction = $direction === self::DIRECTION_ASCENDING
            ? self::DIRECTION_ASCENDING
            : self::DIRECTION_DESCENDING;
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
            $request->query->get('status'),
            $request->query->get('sort', self::DEFAULT_FIELD),
            $request->query->get('direction', self::DEFAULT_DIRECTION)
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
     * Returns the field the list is ordered by.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Returns the getter of the field the list is ordered by.
     *
     * @return string
     */
    public function getFieldGetter()
    {
        return self::FIELDS[$this->field];
    }

    /**
     * Returns the direction the list is ordered in.
     *
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Returns whether the list is ordered from the highest value downwards.
     *
     * @return bool
     */
    public function isDescending()
    {
        return $this->direction === self::DIRECTION_DESCENDING;
    }

    /**
     * Returns whether the values of a field are compared as numbers.
     *
     * @return bool
     */
    public function isNumericField()
    {
        return in_array($this->field, self::NUMERIC_FIELDS, true);
    }

    /**
     * Returns whether the list is currently ordered by a field.
     *
     * @param string $field
     *
     * @return bool
     */
    public function isSortedBy($field)
    {
        return $this->field === $field;
    }

    /**
     * Returns the direction a column header has to link to: the opposite one
     * for the column in use, ascending for every other column.
     *
     * @param string $field
     *
     * @return string
     */
    public function getToggledDirection($field)
    {
        if (!$this->isSortedBy($field)) {
            return self::DIRECTION_ASCENDING;
        }

        return $this->isDescending() ? self::DIRECTION_ASCENDING : self::DIRECTION_DESCENDING;
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
