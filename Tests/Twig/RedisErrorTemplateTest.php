<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

class RedisErrorTemplateTest extends ListTemplateTestCase
{
    const MESSAGE = 'Connection refused [tcp://127.0.0.1:6379]';

    public function testItNamesWhatIsWrong()
    {
        $output = $this->render(self::MESSAGE, '/srv/app/config/resque.yml');

        $this->assertStringContainsString('No connection to Redis', $output);
        $this->assertStringContainsString('tcp://127.0.0.1:6379', $output);
        $this->assertStringContainsString('/srv/app/config/resque.yml', $output);
    }

    /**
     * The interface is themed with the glyphicons of the bundled bootstrap.
     */
    public function testItCarriesAnIconOfTheTheme()
    {
        $this->assertRegExp('#<span class="glyphicon glyphicon-[a-z-]+"#', $this->render(self::MESSAGE, null));
    }

    public function testItSaysSoWhenNoConfigurationFileIsInUse()
    {
        $output = $this->render(self::MESSAGE, null);

        $this->assertStringContainsString('defaults of php-resque', $output);
    }

    /**
     * The message comes from the client and ends up on the page.
     */
    public function testTheReportOfTheClientIsEscaped()
    {
        $output = $this->render('<script>alert(1)</script>', null);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    private function render($error, $configFile)
    {
        return $this->renderTemplate('Error/redis.html.twig', [
            'error' => $error,
            'configFile' => $configFile,
        ]);
    }
}
