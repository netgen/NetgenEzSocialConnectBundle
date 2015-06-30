<?php

namespace Netgen\Bundle\EzSocialConnectBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root( 'netgen_ez_social_connect' );

        $rootNode
            ->children()
            ->arrayNode( 'resource_owners' )
                ->useAttributeAsKey( 'resource_owner_name' )
                ->prototype( 'array' )
                    ->children()
                        ->scalarNode( 'useConfigResolver' )->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
