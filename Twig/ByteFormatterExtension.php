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

class ByteFormatterExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('formatBytes', [$this, 'formatBytes']),
        ];
    }

    public function formatBytes($bytes)
    {
        return Util::bytes($bytes);
    }
}
