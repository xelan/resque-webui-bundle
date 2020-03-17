<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Twig_Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;

class JobController extends AbstractController
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * Constructor.
     *
     * @param Twig_Environment   $twig
     * @param ResqueConfigurator $configurator
     * @param JobFactory         $jobFactory
     */
    public function __construct(
        Twig_Environment $twig,
        ResqueConfigurator $configurator,
        JobFactory $jobFactory
    ) {

        parent::__construct($twig, $configurator);

        $this->jobFactory = $jobFactory;
    }

    public function indexAction()
    {
        $jobs = $this->jobFactory->createAll();

        return $this->render('AndarisResqueWebUiBundle:Job:index.html.twig', ['jobs' => $jobs]);
    }

    public function detailsAction($jobId)
    {
        $job = $this->jobFactory->createById($jobId);

        if ($job === null) {
            throw new NotFoundHttpException('Job not found!');
        }

        return $this->render('AndarisResqueWebUiBundle:Job:details.html.twig', ['job' => $job]);
    }
}
