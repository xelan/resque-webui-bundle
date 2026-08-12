<?php
/**
 * Resque Web UI Application.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use ReflectionMethod;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'andaris_resque_web_ui';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = $this->createTreeBuilder();

        $this->getRootNode($treeBuilder)
            ->children()
                ->scalarNode('config_file')
                    ->defaultNull()
                    ->info('Path of the php-resque configuration file.')
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * Symfony 4.2 turned the root node name into a constructor argument and
     * dropped the argument-less constructor in 5.0.
     *
     * @return TreeBuilder
     */
    private function createTreeBuilder()
    {
        $constructor = new ReflectionMethod(TreeBuilder::class, '__construct');

        if ($constructor->getNumberOfRequiredParameters() > 0) {
            return new TreeBuilder(self::ROOT_NODE);
        }

        return new TreeBuilder();
    }

    /**
     * Symfony 4.2 added getRootNode() and removed root() in 5.0.
     *
     * @param TreeBuilder $treeBuilder
     *
     * @return ArrayNodeDefinition
     */
    private function getRootNode(TreeBuilder $treeBuilder)
    {
        if (method_exists($treeBuilder, 'getRootNode')) {
            return $treeBuilder->getRootNode();
        }

        return $treeBuilder->root(self::ROOT_NODE);
    }
}
