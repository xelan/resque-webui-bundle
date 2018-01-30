<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2018 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

class Host
{
    /**
     * @var string
     */
    private $hostname;

    /**
     * @var Worker[]
     */
    private $workers;

    /**
     * Constructor.
     *
     * @param string   $hostname
     * @param Worker[] $workers
     */
    public function __construct($hostname, $workers = [])
    {
        $this->hostname = $hostname;
        $this->workers = $workers;
    }

    /**
     * Returns the hostname of the host.
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Returns the workers of the host.
     *
     * @return Worker[]
     */
    public function getWorkers()
    {
        return $this->workers;
    }

    /**
     * Returns the number of workers of the host.
     *
     * @return int
     */
    public function getNumberWorkers()
    {
        return count($this->workers);
    }
}
