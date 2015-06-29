<?php

namespace Netgen\Bundle\EzSocialConnectBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Netgen\Bundle\EzSocialConnectBundle\DependencyInjection\Configuration;

class AddConfigResolverCompilerPass implements  CompilerPassInterface
{
    public function process( ContainerBuilder $container )
    {
        $extensionConfig = $container->getExtensionConfig('netgen_ez_social_connect');

        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration( $configuration, $extensionConfig );

        $resourceOwners = !empty($config["resource_owners"]) ? $config["resource_owners"] : null;

        foreach( $resourceOwners as $name => $owner )
        {
            if( $owner["useConfigResolver"] )
            {
                $resourceOwnerDefinition = $container->findDefinition('hwi_oauth.resource_owner.' . $name);
                $resourceOwnerDefinition->addMethodCall('setConfigResolver', array(new Reference( 'ezpublish.config.resolver' )));
            }
        }
    }
}