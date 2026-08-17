<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;
use Andaris\ResqueWebUiBundle\Exception\JobNotRepeatableException;
use Andaris\ResqueWebUiBundle\Exception\RedisUnavailableException;

class JobController extends AbstractController
{
    const RETRY_TOKEN = 'job_retry';

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * Constructor.
     *
     * @param Environment        $twig
     * @param ResqueConfigurator $configurator
     * @param JobFactory                $jobFactory
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param UrlGeneratorInterface     $router
     */
    public function __construct(
        Environment $twig,
        ResqueConfigurator $configurator,
        JobFactory $jobFactory,
        CsrfTokenManagerInterface $csrfTokenManager,
        UrlGeneratorInterface $router
    ) {

        parent::__construct($twig, $configurator);

        $this->jobFactory = $jobFactory;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->router = $router;
    }

    public function indexAction(Request $request)
    {
        $criteria = JobCriteria::fromRequest($request);

        // the full list is read once: the counts of the filter describe every
        // job of the queue in view, not just the ones that are on screen
        try {
            $jobs = $this->jobFactory->createAll($criteria);
        } catch (RedisUnavailableException $failure) {
            return $this->renderRedisUnavailable($failure);
        }

        // the queue is narrowed down first, so that the counts describe what
        // the status filter picks from rather than contradicting the table
        $onQueue = array_values(array_filter($jobs, [$criteria, 'matchesQueue']));

        return $this->render('@AndarisResqueWebUi/Job/index.html.twig', [
            'jobs' => array_values(array_filter($onQueue, [$criteria, 'matchesStatus'])),
            'criteria' => $criteria,
            'counts' => $this->countByStatus($onQueue),
            'total' => count($onQueue),
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

    public function detailsAction($jobId, Request $request)
    {
        try {
            $job = $this->jobFactory->createById($jobId);

            if ($job === null) {
                throw new NotFoundHttpException('Job not found!');
            }

            $retried = $this->readRetriedJob($request);
        } catch (RedisUnavailableException $failure) {
            return $this->renderRedisUnavailable($failure);
        }

        return $this->render('@AndarisResqueWebUi/Job/details.html.twig', [
            'job' => $job,
            'retryToken' => $this->csrfTokenManager->getToken(self::RETRY_TOKEN)->getValue(),
            'retriedJob' => $retried,
            'retryFailure' => $this->readRetryFailure($request),
        ]);
    }

    /**
     * Queues the job again and returns to it, naming the job that came out.
     *
     * The outcome travels in the query string rather than in a flash message:
     * the bundle brings no session of its own, and the application it is
     * mounted in does not have to have one either.
     *
     * @param string  $jobId
     * @param Request $request
     *
     * @return Response
     */
    public function retryAction($jobId, Request $request)
    {
        // read out of the whole body rather than through get(): a form field
        // can just as well arrive as an array, which get() answers differently
        // from one version of HttpFoundation to the next
        $submitted = $request->request->all();
        $sent = isset($submitted['_token']) && is_string($submitted['_token']) ? $submitted['_token'] : '';

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::RETRY_TOKEN, $sent))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        try {
            $job = $this->jobFactory->createById($jobId);

            if ($job === null) {
                throw new NotFoundHttpException('Job not found!');
            }

            $retried = $this->jobFactory->recreate($job);
        } catch (RedisUnavailableException $failure) {
            return $this->renderRedisUnavailable($failure);
        } catch (JobNotRepeatableException $failure) {
            return $this->redirectToJob($jobId, ['retryFailure' => $failure->getReason()]);
        }

        return $this->redirectToJob($jobId, ['retried' => $retried->getId()]);
    }

    /**
     * Returns the job a retry produced, as far as it is still there to link to.
     *
     * @param Request $request
     *
     * @return Job|null
     */
    private function readRetriedJob(Request $request)
    {
        $id = $request->query->get('retried');

        if (!is_string($id) || $id === '') {
            return null;
        }

        return $this->jobFactory->createById($id);
    }

    /**
     * Returns why a retry did not happen, as one of the known reasons.
     *
     * The page says so out of a fixed set of sentences: an address is written
     * by whoever hands it out, and a message taken from it verbatim would let
     * a link put words of its own into this interface.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function readRetryFailure(Request $request)
    {
        $reason = $request->query->get('retryFailure');

        if (!is_string($reason) || !in_array($reason, JobNotRepeatableException::REASONS, true)) {
            return null;
        }

        return $reason;
    }

    /**
     * @param string $jobId
     * @param array  $parameters
     *
     * @return RedirectResponse
     */
    private function redirectToJob($jobId, array $parameters)
    {
        $url = $this->router->generate(
            'andaris_resque_web_ui_job',
            array_merge(['jobId' => $jobId], $parameters)
        );

        return new RedirectResponse($url);
    }
}
