<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Resque\Job;
use Twig\TwigFilter;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;
use Andaris\ResqueWebUiBundle\Twig\JobStatusFormatterExtension;

use InvalidArgumentException;

class JobStatusFormatterExtensionTest extends TestCase
{
    /**
     * @var JobStatusFormatterExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new JobStatusFormatterExtension(new JobAdapter());
    }

    public function testItRegistersTheFormatJobStatusFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('formatJobStatus', $filters[0]->getName());
    }

    public function testItDelegatesToTheAdapter()
    {
        $this->assertSame('Running', $this->extension->formatJobStatus(Job::STATUS_RUNNING));
        $this->assertSame('Failed', $this->extension->formatJobStatus(Job::STATUS_FAILED));
    }

    public function testAnUnknownStatusBubblesUpAsAnException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status "42"!');

        $this->extension->formatJobStatus(42);
    }
}
