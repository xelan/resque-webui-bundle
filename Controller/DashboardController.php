<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Resque;
use Andaris\ResqueWebUiBundle\AndarisResqueWebUiBundle;

class DashboardController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('@AndarisResqueWebUi/Dashboard/index.html.twig');
    }

    public function aboutAction()
    {
        $info = [
            'phpVersion' => PHP_VERSION,
            'resqueVersion' => Resque::VERSION,
            'webUiVersion' => AndarisResqueWebUiBundle::VERSION,
            'configFile' => $this->configurator->getConfigFile() ?: 'none, using defaults',
        ];

        return $this->render('@AndarisResqueWebUi/Dashboard/about.html.twig', ['info' => $info]);
    }
}
