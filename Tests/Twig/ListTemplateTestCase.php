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
 * Renders one of the lists against a stubbed layout, so that the sorting links
 * are covered rather than only parsed.
 */
abstract class ListTemplateTestCase extends TestCase
{
    const LAYOUT = '@AndarisResqueWebUi/layout.html.twig';
    const MACROS = '@AndarisResqueWebUi/macros.html.twig';

    /**
     * Renders a template of the bundle with the given parameters.
     *
     * @param string $template the path below Resources/views
     * @param array  $parameters
     *
     * @return string
     */
    protected function renderTemplate($template, array $parameters)
    {
        $name = '@AndarisResqueWebUi/' . $template;

        $twig = new Environment(new ArrayLoader([
            self::LAYOUT => '{% block content %}{% endblock %}',
            self::MACROS => $this->readTemplate('macros.html.twig'),
            $name => $this->readTemplate($template),
        ]));

        $twig->addExtension(new ByteFormatterExtension());
        $twig->addExtension(new FailureRateSeverityExtension());
        $twig->addExtension(new HumanTimeDiffFormatterExtension());
        $twig->addExtension(new TimeFormatterExtension());
        $twig->addExtension(new JobStatusFormatterExtension(new JobAdapter()));
        $twig->addExtension(new JsonFormatterExtension());
        $twig->addExtension(new WorkerStatusFormatterExtension(new WorkerAdapter()));

        $twig->addFunction(new TwigFunction('path', function ($route, array $parameters = []) {
            return '/' . $route . '?' . http_build_query($parameters);
        }));

        $twig->addFunction(new TwigFunction('asset', function ($path) {
            return '/' . $path;
        }));

        return $twig->render($name, $parameters);
    }

    /**
     * Returns the sorting links of the rendered table, indexed by column label.
     *
     * @param string $output
     *
     * @return string[]
     */
    protected function headerLinks($output)
    {
        $pattern = '#<th[^>]*>\s*<a href="([^"]+)">\s*([^<\s][^<]*?)\s*(?:<span|</a>)#s';

        preg_match_all($pattern, $output, $matches, PREG_SET_ORDER);

        $links = [];

        foreach ($matches as $match) {
            $links[trim($match[2])] = html_entity_decode($match[1]);
        }

        return $links;
    }

    private function readTemplate($path)
    {
        return file_get_contents(dirname(dirname(__DIR__)) . '/Resources/views/' . $path);
    }
}
