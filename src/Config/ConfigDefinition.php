<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Config;

use function is_numeric;
use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $parametersNode */
        $parametersNode = $builder->root('parameters');
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('baseUrl')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(function ($value) {
                            preg_match('/^([a-z]*)?:\/\//', strtolower($value), $match);
                            if (isset($match[1])) {
                                return !in_array($match[1], ['http', 'https']);
                            }
                            return false;
                        })
                        ->thenInvalid('Protocol is not valid. Only http and https are allowed.')
                        ->end()
                ->end()
                ->scalarNode('path')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('maxRedirects')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !is_numeric($value) || $value < 0;
                        })
                        ->thenInvalid('Max redirects must be positive integer')
                        ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
