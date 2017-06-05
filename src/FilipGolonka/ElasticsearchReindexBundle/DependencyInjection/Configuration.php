<?php

namespace FilipGolonka\ElasticsearchReindexBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('filip_golonka_elasticsearch_reindex');

        $rootNode
            ->children()
                ->scalarNode('alias_name')->end()
                ->scalarNode('elasticsearch_client')->end()
                ->scalarNode('index_name_template')->end()
                ->scalarNode('index_type')->end()
                ->variableNode('mapping')->end()
                ->scalarNode('reindex_command_name')->end()
                ->variableNode('settings')->end()
            ->end();

        return $treeBuilder;
    }
}
