<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Exception;

use RuntimeException;

/**
 * A job could not be queued again.
 *
 * What a job is made of is read back out of its payload, which is written by
 * whatever queued it in the first place, so it is not necessarily something
 * this interface can make a new job out of. Queueing can also be refused by a
 * listener of php-resque itself.
 *
 * Every reason has a name of its own: the page the retry returns to says what
 * went wrong, and it says it from a fixed set of sentences rather than from
 * whatever the address happens to carry.
 */
class JobNotRepeatableException extends RuntimeException
{
    const REASON_PAYLOAD = 'payload';
    const REASON_CLASS = 'class';
    const REASON_REFUSED = 'refused';

    const REASONS = [self::REASON_PAYLOAD, self::REASON_CLASS, self::REASON_REFUSED];

    /**
     * @var string
     */
    private $reason;

    /**
     * @param string $id
     *
     * @return JobNotRepeatableException
     */
    public static function payloadIsNotReadable($id)
    {
        return self::because(
            self::REASON_PAYLOAD,
            sprintf('The payload of the job "%s" is not valid JSON.', $id)
        );
    }

    /**
     * @param string $id
     *
     * @return JobNotRepeatableException
     */
    public static function payloadNamesNoClass($id)
    {
        return self::because(
            self::REASON_CLASS,
            sprintf('The payload of the job "%s" does not name the class to run.', $id)
        );
    }

    /**
     * @param string $id
     *
     * @return JobNotRepeatableException
     */
    public static function queueingWasRefused($id)
    {
        return self::because(
            self::REASON_REFUSED,
            sprintf('Queueing the job "%s" again was refused by php-resque.', $id)
        );
    }

    /**
     * Returns which of the reasons this is, as one of the REASON_ constants.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     * @param string $message
     *
     * @return JobNotRepeatableException
     */
    private static function because($reason, $message)
    {
        $failure = new self($message);
        $failure->reason = $reason;

        return $failure;
    }
}
