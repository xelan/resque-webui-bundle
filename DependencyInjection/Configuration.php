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
     * Symfony 4.2 added getRootNode() and the constructor argument that creates
     * the node it hands out, and 5.0 dropped root() and the argument-less
     * constructor. Both halves are therefore decided by the same question.
     *
     * @return TreeBuilder
     */
    private function createTreeBuilder()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            return new TreeBuilder(self::ROOT_NODE);
        }

        return new TreeBuilder();
    }

    /**
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
