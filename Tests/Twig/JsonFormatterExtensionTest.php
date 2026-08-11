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

use Andaris\ResqueWebUiBundle\Twig\JsonFormatterExtension;

class JsonFormatterExtensionTest extends TestCase
{
    /**
     * @var JsonFormatterExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new JsonFormatterExtension();
    }

    public function testItRegistersThePrettyPrintFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('prettyPrintJson', $filters[0]->getName());
    }

    public function testItIndentsAJsonObject()
    {
        $expected = "{\n    \"class\": \"SendMail\",\n    \"attempts\": 3\n}";

        $this->assertSame($expected, $this->extension->prettyPrintJson('{"class":"SendMail","attempts":3}'));
    }

    public function testItLeavesSlashesUnescaped()
    {
        $this->assertSame(
            "{\n    \"url\": \"http://example.org/jobs\"\n}",
            $this->extension->prettyPrintJson('{"url":"http:\/\/example.org\/jobs"}')
        );
    }

    /**
     * A job without a payload must not be rendered as the literal "null".
     *
     * @dataProvider emptyPayloadProvider
     */
    public function testAnEmptyPayloadRendersAsAnEmptyString($payload)
    {
        $this->assertSame('', $this->extension->prettyPrintJson($payload));
    }

    public function emptyPayloadProvider()
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    /**
     * Anything that is not decodable is shown unchanged rather than as "null",
     * so a broken payload stays inspectable in the UI.
     *
     * @dataProvider invalidJsonProvider
     */
    public function testInvalidJsonIsReturnedUnchanged($payload)
    {
        $this->assertSame($payload, $this->extension->prettyPrintJson($payload));
    }

    public function invalidJsonProvider()
    {
        return [
            'plain text' => ['not json at all'],
            'truncated object' => ['{"class":"SendMail"'],
            'single brace' => ['{'],
            'serialized php' => ['a:1:{s:5:"class";s:8:"SendMail";}'],
        ];
    }

    /**
     * The literal null payload is valid JSON and stays "null".
     *
     * @dataProvider nullLiteralProvider
     */
    public function testTheJsonNullLiteralIsKept($payload)
    {
        $this->assertSame('null', $this->extension->prettyPrintJson($payload));
    }

    public function nullLiteralProvider()
    {
        return [
            'lowercase' => ['null'],
            'uppercase' => ['NULL'],
            'padded' => ["  null\n"],
        ];
    }

    public function testScalarJsonValuesArePassedThrough()
    {
        $this->assertSame('3', $this->extension->prettyPrintJson('3'));
        $this->assertSame('true', $this->extension->prettyPrintJson('true'));
        $this->assertSame('"text"', $this->extension->prettyPrintJson('"text"'));
    }
}
