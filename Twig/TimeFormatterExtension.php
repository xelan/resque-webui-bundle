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

class TimeFormatterExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('formatTime', [$this, 'formatTime']),
        ];
    }

    public function formatTime($time)
    {
        if ($time === null) {
            return '-';
        }

        return date('Y-m-d H:i:s', $time);
    }
}
