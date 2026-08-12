<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Exception;

use Predis\CommunicationException;

use RuntimeException;

/**
 * The configured Redis server could not be talked to.
 *
 * php-resque talks to Redis through Predis, whose communication failures cover
 * a refused connection, a timeout and a connection lost midway. They are
 * translated here so that neither the controllers nor the templates have to
 * know which client the queue library happens to use.
 */
class RedisUnavailableException extends RuntimeException
{
    /**
     * Translates a Predis communication failure.
     *
     * @param CommunicationException $failure
     *
     * @return RedisUnavailableException
     */
    public static function fromCommunicationFailure(CommunicationException $failure)
    {
        return new self($failure->getMessage(), 0, $failure);
    }
}
