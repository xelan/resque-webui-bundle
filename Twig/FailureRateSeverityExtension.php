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

class FailureRateSeverityExtension extends AbstractExtension
{
    const WARNING_THRESHOLD = 1.0;
    const DANGER_THRESHOLD = 10.0;

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('failureRateSeverity', [$this, 'failureRateSeverity']),
        ];
    }

    /**
     * Returns how bad a failure rate is, named the way the stylesheet colours
     * it: below one percent is what a healthy queue looks like, below ten
     * percent is worth a look, anything above that is a queue in trouble.
     *
     * The name is returned rather than the markup around it, so that whoever
     * shows the rate stays in charge of how it is shown.
     *
     * @param float|null $rate the percentage of failed jobs, null for a queue
     *                         that has never seen one
     *
     * @return string
     */
    public function failureRateSeverity($rate)
    {
        if ($rate === null) {
            return 'muted';
        }

        if ($rate < self::WARNING_THRESHOLD) {
            return 'success';
        }

        return $rate < self::DANGER_THRESHOLD ? 'warning' : 'danger';
    }
}
