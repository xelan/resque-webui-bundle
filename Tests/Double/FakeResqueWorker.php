<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Double;

/**
 * Stand-in for Resque\Worker, which is final as of php-resque 4 and can
 * therefore not be mocked. WorkerFactory type hints neither the worker nor its
 * packet, so a plain double serves every supported major version.
 */
class FakeResqueWorker
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $packet;

    /**
     * @param string $id
     * @param array  $packet
     */
    public function __construct($id, array $packet)
    {
        $this->id = $id;
        $this->packet = $packet;
    }

    public function __toString()
    {
        return $this->id;
    }

    public function getPacket()
    {
        return $this->packet;
    }
}
