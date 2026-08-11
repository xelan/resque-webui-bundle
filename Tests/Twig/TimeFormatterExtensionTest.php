<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

use Andaris\ResqueWebUiBundle\Twig\TimeFormatterExtension;

class TimeFormatterExtensionTest extends TestCase
{
    /**
     * @var TimeFormatterExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new TimeFormatterExtension();
    }

    public function testItRegistersTheFormatTimeFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('formatTime', $filters[0]->getName());
    }

    public function testItFormatsAUnixTimestamp()
    {
        $this->assertSame('2017-07-14 02:40:00', $this->extension->formatTime(1500000000));
    }

    public function testItFormatsTheEpoch()
    {
        $this->assertSame('1970-01-01 00:00:00', $this->extension->formatTime(0));
    }

    /**
     * Job timestamps are null while the job has not reached that stage yet.
     */
    public function testAMissingTimestampRendersAsADash()
    {
        $this->assertSame('-', $this->extension->formatTime(null));
    }

    /**
     * Redis returns the timestamps as strings.
     */
    public function testItAcceptsANumericString()
    {
        $this->assertSame('2017-07-14 02:40:00', $this->extension->formatTime('1500000000'));
    }
}
