<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;
use Andaris\ResqueWebUiBundle\Exception\RedisUnavailableException;

class JobController extends AbstractController
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * Constructor.
     *
     * @param Environment        $twig
     * @param ResqueConfigurator $configurator
     * @param JobFactory         $jobFactory
     */
    public function __construct(
        Environment $twig,
        ResqueConfigurator $configurator,
        JobFactory $jobFactory
    ) {

        parent::__construct($twig, $configurator);

        $this->jobFactory = $jobFactory;
    }

    public function indexAction(Request $request)
    {
        $criteria = JobCriteria::fromRequest($request);

        // the full list is read once: the counts of the filter describe every
        // job, not just the ones that are on screen
        try {
            $jobs = $this->jobFactory->createAll($criteria);
        } catch (RedisUnavailableException $failure) {
            return $this->renderRedisUnavailable($failure);
        }

        return $this->render('@AndarisResqueWebUi/Job/index.html.twig', [
            'jobs' => array_values(array_filter($jobs, [$criteria, 'matches'])),
            'criteria' => $criteria,
            'counts' => $this->countByStatus($jobs),
            'total' => count($jobs),
        ]);
    }

    /**
     * Returns the number of jobs per status, indexed by status.
     *
     * @param Job[] $jobs
     *
     * @return int[]
     */
    private function countByStatus(array $jobs)
    {
        $counts = array_fill_keys(JobCriteria::STATUSES, 0);

        foreach ($jobs as $job) {
            $status = (int) $job->getStatus();

            if (array_key_exists($status, $counts)) {
                ++$counts[$status];
            }
        }

        return $counts;
    }

    public function detailsAction($jobId)
    {
        try {
            $job = $this->jobFactory->createById($jobId);
        } catch (RedisUnavailableException $failure) {
            return $this->renderRedisUnavailable($failure);
        }

        if ($job === null) {
            throw new NotFoundHttpException('Job not found!');
        }

        return $this->render('@AndarisResqueWebUi/Job/details.html.twig', ['job' => $job]);
    }
}
