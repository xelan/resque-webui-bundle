<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2018 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

use Twig_Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;

abstract class AbstractController
{
    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var ResqueConfigurator
     */
    protected $configurator;

    /**
     * Constructor.
     *
     * @param Twig_Environment   $twig
     * @param ResqueConfigurator $configurator
     */
    public function __construct(Twig_Environment $twig, ResqueConfigurator $configurator)
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
