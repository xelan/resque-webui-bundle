<?php
/**
 * Resque Web UI Application.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AndarisResqueWebUiExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    const CONFIG_FILE_PARAMETER = 'andaris_resque_web_ui_config';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $configFile = $config['config_file'];

        if ($configFile === null) {
            $configFile = $this->getDefaultConfigFile($container);
        }

        $container->setParameter(self::CONFIG_FILE_PARAMETER, $configFile);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Returns where the php-resque configuration file is looked for when the
     * application does not say.
     *
     * Symfony 5 removed the kernel.root_dir parameter along with the app
     * directory it pointed at, so the modern layout is assumed whenever that
     * parameter is gone.
     *
     * @param ContainerBuilder $container
     *
     * @return string
     */
    private function getDefaultConfigFile(ContainerBuilder $container)
    {
        if ($container->hasParameter('kernel.root_dir')) {
            return '%kernel.root_dir%/config/resque.yml';
        }

        return '%kernel.project_dir%/config/resque.yml';
    }
}
