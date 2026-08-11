<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Dto\Host;
use Andaris\ResqueWebUiBundle\Dto\Worker;

class HostTest extends TestCase
{
    public function testItExposesTheHostnameAndItsWorkers()
    {
        $workers = [$this->createWorker('host:1:default'), $this->createWorker('host:2:default')];

        $host = new Host('worker-node-1', $workers);

        $this->assertSame('worker-node-1', $host->getHostname());
        $this->assertSame($workers, $host->getWorkers());
        $this->assertSame(2, $host->getNumberWorkers());
    }

    public function testAHostWithoutWorkersCountsZero()
    {
        $host = new Host('worker-node-1');

        $this->assertSame([], $host->getWorkers());
        $this->assertSame(0, $host->getNumberWorkers());
    }

    private function createWorker($id)
    {
        return new Worker($id, 2, 1500000000, null, 0, 0, 0, 0, 5, 60, 1048576, 128);
    }
}
