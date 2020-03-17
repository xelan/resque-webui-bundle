<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

use Andaris\ResqueWebUiBundle\Adapter\WorkerAdapter;

class WorkerStatusFormatterExtension extends Twig_Extension
{
    private $workerAdapter;

    /**
     * Constructor.
     *
     * @param WorkerAdapter $workerAdapter
     */
    public function __construct(WorkerAdapter $workerAdapter)
    {
        $this->workerAdapter = $workerAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('formatWorkerStatus', [$this, 'formatWorkerStatus']),
        ];
    }

    public function formatWorkerStatus($status)
    {
        return $this->workerAdapter->getStatusText($status);
    }
}
