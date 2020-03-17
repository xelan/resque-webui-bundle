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

class TimeFormatterExtension extends Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('formatTime', [$this, 'formatTime']),
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
