<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Source;
use Twig\TwigFunction;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;
use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;
use Andaris\ResqueWebUiBundle\Twig\ByteFormatterExtension;
use Andaris\ResqueWebUiBundle\Twig\FailureRateSeverityExtension;
use Andaris\ResqueWebUiBundle\Twig\HumanTimeDiffFormatterExtension;
use Andaris\ResqueWebUiBundle\Twig\JobStatusFormatterExtension;
use Andaris\ResqueWebUiBundle\Twig\JsonFormatterExtension;
use Andaris\ResqueWebUiBundle\Twig\TimeFormatterExtension;
use Andaris\ResqueWebUiBundle\Twig\WorkerStatusFormatterExtension;

/**
 * The templates are never touched by the other tests, so a syntax error in one
 * of them would only show up in a browser.
 */
class TemplateSyntaxTest extends TestCase
{
    /**
     * @dataProvider templateProvider
     */
    public function testTheTemplateCompiles($file)
    {
        $twig = $this->createEnvironment();
        $source = new Source(file_get_contents($file), basename($file));

        $twig->parse($twig->tokenize($source));

        $this->addToAssertionCount(1);
    }

    public function templateProvider()
    {
        $directory = dirname(dirname(__DIR__)) . '/Resources/views';

        $templates = array_merge(
            glob($directory . '/*.html.twig'),
            glob($directory . '/*/*.html.twig')
        );

        $cases = [];

        foreach ($templates as $template) {
            $cases[str_replace($directory . '/', '', $template)] = [$template];
        }

        return $cases;
    }

    private function createEnvironment()
    {
        $twig = new Environment(new ArrayLoader([]));

        $twig->addExtension(new ByteFormatterExtension());
        $twig->addExtension(new FailureRateSeverityExtension());
        $twig->addExtension(new HumanTimeDiffFormatterExtension());
        $twig->addExtension(new TimeFormatterExtension());
        $twig->addExtension(new JsonFormatterExtension());
        $twig->addExtension(new JobStatusFormatterExtension(new JobAdapter()));
        $twig->addExtension(new WorkerStatusFormatterExtension(new WorkerAdapter()));

        // provided by the framework at runtime
        foreach (['asset', 'path', 'logout_url'] as $function) {
            $twig->addFunction(new TwigFunction($function, function () {
                return '';
            }));
        }

        return $twig;
    }
}
