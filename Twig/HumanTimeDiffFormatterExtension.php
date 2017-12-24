<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Twig;

use Resque\Helpers\Util;
use Twig_Extension;
use Twig_SimpleFilter;

class HumanTimeDiffFormatterExtension extends Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('formatHumanTimeDiff', [$this, 'formatHumanTimeDiff']),
        ];
    }

    public function formatHumanTimeDiff($from, $to = null)
    {
        return Util::human_time_diff($from, $to);
    }
}
