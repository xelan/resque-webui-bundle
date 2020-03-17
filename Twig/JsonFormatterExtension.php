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

class JsonFormatterExtension extends Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('prettyPrintJson', [$this, 'prettyPrintJson']),
        ];
    }

    public function prettyPrintJson($json)
    {
        return json_encode(json_decode($json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
