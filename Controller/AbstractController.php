<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;

abstract class AbstractController
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var ResqueConfigurator
     */
    protected $configurator;

    /**
     * Constructor.
     *
     * @param Environment        $twig
     * @param ResqueConfigurator $configurator
     */
    public function __construct(Environment $twig = null, ResqueConfigurator $configurator = null)
    {
        $this->twig = $twig;
        $this->configurator = $configurator;

        $configurator->loadConfig();
    }

    /**
     * Returns a Response with a rendered view.
     *
     * @param string $view
     * @param array  $parameters
     *
     * @return Response
     */
    protected function render($view, array $parameters = [])
    {
        return new Response($this->twig->render($view, $parameters));
    }
}
