<?php
/**
 * Resque Web UI Application.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Andaris\ResqueWebUiBundle\DependencyInjection\AndarisResqueWebUiExtension;
use Andaris\ResqueWebUiBundle\DependencyInjection\Configuration;

class AndarisResqueWebUiExtensionTest extends TestCase
{
    /**
     * The configuration tree is built through APIs that changed twice, so the
     * tree has to come out the same on every supported Symfony version.
     */
    public function testTheConfigurationTreeIsBuilt()
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        $this->assertArrayHasKey('config_file', $config);
        $this->assertNull($config['config_file']);
    }

    public function testTheConfigurationFileCanBeSet()
    {
        $config = (new Processor())->processConfiguration(
            new Configuration(),
            [['config_file' => '/etc/resque/resque.yml']]
        );

        $this->assertSame('/etc/resque/resque.yml', $config['config_file']);
    }

    public function testTheServicesAreRegistered()
    {
        $container = $this->load();

        $this->assertTrue($container->hasDefinition('andaris_resque_web_ui.controller.dashboard'));
        $this->assertTrue($container->hasDefinition('andaris_resque_web_ui.controller.job'));
        $this->assertTrue($container->hasDefinition('andaris_resque_web_ui.controller.metrics'));
        $this->assertTrue($container->hasDefinition('andaris_resque_web_ui.factory.job'));
    }

    public function testAnExplicitConfigurationFileIsUsed()
    {
        $container = $this->load([['config_file' => '/etc/resque/resque.yml']]);

        $this->assertSame(
            '/etc/resque/resque.yml',
            $container->getParameter(AndarisResqueWebUiExtension::CONFIG_FILE_PARAMETER)
        );
    }

    /**
     * Symfony 3 and 4 keep the app directory, so the file is looked for there.
     */
    public function testTheLegacyLayoutIsDetectedByTheKernelRootDirectory()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', '/srv/app/app');
        $container->setParameter('kernel.project_dir', '/srv/app');

        (new AndarisResqueWebUiExtension())->load([], $container);

        $this->assertSame(
            '%kernel.root_dir%/config/resque.yml',
            $container->getParameter(AndarisResqueWebUiExtension::CONFIG_FILE_PARAMETER)
        );
    }

    /**
     * Symfony 5 removed the app directory together with kernel.root_dir.
     */
    public function testTheModernLayoutIsAssumedWithoutTheKernelRootDirectory()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '/srv/app');

        (new AndarisResqueWebUiExtension())->load([], $container);

        $this->assertSame(
            '%kernel.project_dir%/config/resque.yml',
            $container->getParameter(AndarisResqueWebUiExtension::CONFIG_FILE_PARAMETER)
        );
    }

    private function load(array $configs = [])
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '/srv/app');

        (new AndarisResqueWebUiExtension())->load($configs, $container);

        return $container;
    }
}
