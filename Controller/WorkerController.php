<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\WorkerFactory;

class WorkerController extends AbstractController
{
    /**
     * @var WorkerFactory
     */
    private $workerFactory;

    /**
     * Constructor.
     *
     * @param Environment        $twig
     * @param ResqueConfigurator $configurator
     * @param WorkerFactory      $workerFactory
     */
    public function __construct(
        Environment $twig,
        ResqueConfigurator $configurator,
        WorkerFactory $workerFactory
    ) {

        parent::__construct($twig, $configurator);

        $this->workerFactory = $workerFactory;
    }

    public function indexAction()
    {
        $workers = $this->workerFactory->createAll();

        return $this->render('AndarisResqueWebUiBundle:Worker:index.html.twig', ['workers' => $workers]);
    }
}
