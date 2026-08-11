<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Adapter;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;

class ResqueConfiguratorTest extends TestCase
{
    /**
     * @var string
     */
    private $configFile;

    protected function setUp(): void
    {
        $this->configFile = tempnam(sys_get_temp_dir(), 'resque-webui-test-');
        file_put_contents($this->configFile, "redis:\n  scheme: tcp\n");
    }

    protected function tearDown(): void
    {
        if (is_file($this->configFile)) {
            unlink($this->configFile);
        }
    }

    public function testGetConfigFileReturnsThePathOfAReadableFile()
    {
        $configurator = new ResqueConfigurator($this->configFile);

        $this->assertSame($this->configFile, $configurator->getConfigFile());
    }

    public function testGetConfigFileReturnsNullForAMissingFile()
    {
        $configurator = new ResqueConfigurator($this->configFile . '-does-not-exist');

        $this->assertNull($configurator->getConfigFile());
    }

    public function testLoadConfigReportsFailureForAMissingFile()
    {
        $configurator = new ResqueConfigurator($this->configFile . '-does-not-exist');

        $this->assertFalse($configurator->loadConfig());
    }
}
