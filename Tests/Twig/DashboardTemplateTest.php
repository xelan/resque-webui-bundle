<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

class DashboardTemplateTest extends ListTemplateTestCase
{
    public function testItIntroducesTheDashboard()
    {
        $output = $this->render();

        $this->assertStringContainsString('logo_large.png', $output);
        $this->assertStringContainsString('php-resque', $output);
    }

    /**
     * @dataProvider cardProvider
     */
    public function testItLinksToTheList($label, $route, $icon)
    {
        $output = $this->render();

        $this->assertRegExp(
            '#<a class="dashboard-card" href="[^"]*' . $route . '[^"]*">\s*'
                . '<span class="glyphicon glyphicon-' . $icon . '"#',
            $output,
            $label . ' has no card linking to its list'
        );
        $this->assertStringContainsString('<span class="dashboard-card-label">' . $label . '</span>', $output);
    }

    public function cardProvider()
    {
        return [
            'workers' => ['Workers', 'andaris_resque_web_ui_workers', 'cog'],
            'queues' => ['Queues', 'andaris_resque_web_ui_queues', 'inbox'],
            'jobs' => ['Jobs', 'andaris_resque_web_ui_jobs', 'tasks'],
        ];
    }

    /**
     * Three cards next to each other on a wide screen, stacked below one.
     */
    public function testTheCardsShareTheWidthOfThePage()
    {
        $this->assertSame(3, substr_count($this->render(), 'class="col-sm-4"'));
    }

    private function render()
    {
        return $this->renderTemplate('Dashboard/index.html.twig', []);
    }
}
