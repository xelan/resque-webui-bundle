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

use Andaris\ResqueWebUiBundle\Twig\FailureRateSeverityExtension;

class FailureRateSeverityExtensionTest extends TestCase
{
    /**
     * @var FailureRateSeverityExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new FailureRateSeverityExtension();
    }

    public function testItRegistersTheFailureRateSeverityFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('failureRateSeverity', $filters[0]->getName());
    }

    /**
     * @dataProvider severityProvider
     */
    public function testItSaysHowBadTheRateIs($rate, $expected)
    {
        $this->assertSame($expected, $this->extension->failureRateSeverity($rate));
    }

    public function severityProvider()
    {
        return [
            'nothing failed' => [0.0, 'success'],
            'a fraction of a percent' => [0.4, 'success'],
            'just below one percent' => [0.99, 'success'],
            'one percent' => [1.0, 'warning'],
            'just below ten percent' => [9.99, 'warning'],
            'ten percent' => [10.0, 'danger'],
            'everything failed' => [100.0, 'danger'],
            'no jobs at all' => [null, 'muted'],
        ];
    }
}
