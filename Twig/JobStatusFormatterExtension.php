<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2018 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

use Andaris\ResqueWebUiBundle\Adapter\JobAdapter;

class JobStatusFormatterExtension extends Twig_Extension
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
            new Twig_SimpleFilter('formatJobStatus', [$this, 'formatJobStatus']),
        ];
    }

    public function formatJobStatus($status)
    {
        return $this->jobAdapter->getStatusText($status);
    }
}
