<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

/*
 * php-resque 4 moved the top level Resque class into the Resque namespace when
 * it switched from PSR-0 to PSR-4 autoloading. The alias keeps the call sites
 * working on every supported major version. Resque\Resque forwards loadConfig()
 * and setConfig() to Resque\Config through __callStatic(), so the class alias
 * covers the configuration handling as well.
 *
 * On php-resque 2 and 3 the global class exists in its own right and no alias
 * is created.
 */

if (!class_exists('Resque', false) && class_exists('Resque\Resque')) {
    class_alias('Resque\Resque', 'Resque');
}
