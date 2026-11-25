<?php

namespace NS\DistanceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('ns_distance');
        $builder->getRootNode()
            ->children()
                ->scalarNode('api_key')->isRequired()->end()
            ->end();

        return $builder;
    }
}
