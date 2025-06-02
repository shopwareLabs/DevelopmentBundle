<?php

namespace Shopware\Development\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('shopware_development');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('twig')
                    ->children()
                        ->arrayNode('exclude_keywords')
                            ->scalarPrototype()
            ->end()
        ;

        return $treeBuilder;
    }
}
