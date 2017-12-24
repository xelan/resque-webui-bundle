<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Adapter;

use Resque;

/**
 * Helper class for configuring the Resque instance.
 */
class ResqueConfigurator
{
    /**
     * @var string
     */
    private $configFile;

    /**
     * Constructor.
     *
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * Applies the configuration file to the Resque instance.
     *
     * @return bool
     */
    public function loadConfig()
    {
        if (file_exists($this->configFile) && is_readable($this->configFile)) {
            return Resque::loadConfig($this->configFile);
        }

        return false;
    }

    /**
     * Returns the configuration file path or null if it can't be read.
     *
     * @return string
     */
    public function getConfigFile()
    {
        if (file_exists($this->configFile) && is_readable($this->configFile)) {
            return $this->configFile;
        }

        return null;
    }
}
