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

use Andaris\ResqueWebUiBundle\Twig\ByteFormatterExtension;

class ByteFormatterExtensionTest extends TestCase
{
    /**
     * @var ByteFormatterExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new ByteFormatterExtension();
    }

    public function testItRegistersTheFormatBytesFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('formatBytes', $filters[0]->getName());
    }

    /**
     * php-resque formats with decimal (SI) prefixes.
     *
     * @dataProvider byteProvider
     */
    public function testItFormatsTheMemoryUsage($bytes, $expected)
    {
        $this->assertSame($expected, $this->extension->formatBytes($bytes));
    }

    public function byteProvider()
    {
        return [
            'zero' => [0, '0.00 B'],
            'below one kilobyte' => [512, '512.00 B'],
            'one kibibyte' => [1024, '1.02 kB'],
            'megabytes' => [1500000, '1.50 MB'],
            'numeric string from redis' => ['1500000', '1.50 MB'],
        ];
    }
}
