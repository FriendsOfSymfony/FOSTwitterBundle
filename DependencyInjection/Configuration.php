<?php

namespace FOS\TwitterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Configuration for the bundle
 */
class Configuration{

    /**
     * @return \Symfony\Component\Config\Definition\Builder\Symfony\Component\Config\Definition\NodeInterface
     */
    public function getConfigTree(){
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fos_twitter', 'array');

        $rootNode
            ->scalarNode('file')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('enable_security')->defaultValue(false)->end()
            ->scalarNode('consumer_key')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('consumer_secret')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('callback_url')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('anywhere_version')->defaultValue('1')->end()
            ->scalarNode('alias')->defaultNull()->end();

        return $treeBuilder->buildTree();
    }

}

