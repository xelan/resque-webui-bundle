<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\WorkerCriteria;
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

    public function indexAction(Request $request)
    {
        $criteria = WorkerCriteria::fromRequest($request);

        return $this->render('@AndarisResqueWebUi/Worker/index.html.twig', [
            'workers' => $this->workerFactory->createAll($criteria),
            'criteria' => $criteria,
        ]);
    }
}
