<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Resque\Worker;
use Twig\TwigFilter;

use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;
use Andaris\ResqueWebUiBundle\Twig\WorkerStatusFormatterExtension;

use InvalidArgumentException;

class WorkerStatusFormatterExtensionTest extends TestCase
{
    /**
     * @var WorkerStatusFormatterExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new WorkerStatusFormatterExtension(new WorkerAdapter());
    }

    public function testItRegistersTheFormatWorkerStatusFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('formatWorkerStatus', $filters[0]->getName());
    }

    public function testItDelegatesToTheAdapter()
    {
        $this->assertSame('Not started', $this->extension->formatWorkerStatus(Worker::STATUS_NEW));
        $this->assertSame('Paused', $this->extension->formatWorkerStatus(Worker::STATUS_PAUSED));
    }

    public function testAnUnknownStatusBubblesUpAsAnException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status "42"!');

        $this->extension->formatWorkerStatus(42);
    }
}
