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

class JsonFormatterExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('prettyPrintJson', [$this, 'prettyPrintJson']),
        ];
    }

    public function prettyPrintJson($json)
    {
        if ($json === null || $json === '') {
            return '';
        }

        $decoded = json_decode($json, true);

        // not valid JSON, show it unchanged instead of the literal "null"
        if ($decoded === null && strtolower(trim($json)) !== 'null') {
            return $json;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
