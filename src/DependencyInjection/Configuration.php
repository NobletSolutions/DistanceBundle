<?php

namespace NS\DistanceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root    = $builder->root('ns_distance');
        $root
            ->children()
                ->scalarNode('api_key')->isRequired()->end()
            ->end();

        return $builder;
    }
}
