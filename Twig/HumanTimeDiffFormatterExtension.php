<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Twig;

use Resque\Helpers\Util;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HumanTimeDiffFormatterExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('formatHumanTimeDiff', [$this, 'formatHumanTimeDiff']),
        ];
    }

    public function formatHumanTimeDiff($from, $to = null)
    {
        return Util::human_time_diff($from, $to);
    }
}
