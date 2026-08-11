<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;

class JobStatusFormatterExtension extends AbstractExtension
{
    private $jobAdapter;

    /**
     * Constructor.
     *
     * @param JobAdapter $jobAdapter
     */
    public function __construct(JobAdapter $jobAdapter)
    {
        $this->jobAdapter = $jobAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('formatJobStatus', [$this, 'formatJobStatus']),
        ];
    }

    public function formatJobStatus($status)
    {
        return $this->jobAdapter->getStatusText($status);
    }
}
