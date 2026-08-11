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

use Andaris\ResqueWebUiBundle\Twig\HumanTimeDiffFormatterExtension;

class HumanTimeDiffFormatterExtensionTest extends TestCase
{
    /**
     * @var HumanTimeDiffFormatterExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new HumanTimeDiffFormatterExtension();
    }

    public function testItRegistersTheFormatHumanTimeDiffFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('formatHumanTimeDiff', $filters[0]->getName());
    }

    /**
     * @dataProvider timeDiffProvider
     */
    public function testItDescribesTheDistanceBetweenTwoTimestamps($from, $to, $expected)
    {
        $this->assertSame($expected, $this->extension->formatHumanTimeDiff($from, $to));
    }

    public function timeDiffProvider()
    {
        return [
            'seconds' => [0, 30, '30 secs'],
            'an hour' => [0, 3600, '1 hour'],
            'two hours' => [0, 7200, '2 hours'],
            'reversed arguments are absolute' => [7200, 3600, '1 hour'],
        ];
    }

    /**
     * Without an explicit second timestamp the diff is taken against now.
     */
    public function testItFallsBackToTheCurrentTime()
    {
        $this->assertSame('30 secs', $this->extension->formatHumanTimeDiff(time() - 30));
    }

    /**
     * php-resque resolves the second timestamp with "?:", so a falsy value does
     * not mean the epoch but is replaced by the current time as well.
     */
    public function testAFalsySecondTimestampIsTreatedAsNow()
    {
        $this->assertSame('30 secs', $this->extension->formatHumanTimeDiff(time() - 30, 0));
    }
}
