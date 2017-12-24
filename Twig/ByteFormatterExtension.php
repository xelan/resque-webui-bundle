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

class ByteFormatterExtension extends Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('formatBytes', [$this, 'formatBytes']),
        ];
    }

    public function formatBytes($bytes)
    {
        return Util::bytes($bytes);
    }
}
