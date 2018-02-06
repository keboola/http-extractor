<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigDefinition implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('parameters');
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $rootNode
            ->children()
                ->scalarNode('baseUrl')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('path')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('saveAs')
                    ->defaultNull()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $treeBuilder;
    }
}
