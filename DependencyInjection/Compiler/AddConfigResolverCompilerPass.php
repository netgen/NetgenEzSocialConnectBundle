<?php

namespace Netgen\Bundle\EzSocialConnectBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Netgen\Bundle\EzSocialConnectBundle\DependencyInjection\Configuration;

class AddConfigResolverCompilerPass implements CompilerPassInterface
{
    /**
     * Adds config resolver to all configured resource owners.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $extensionConfig = $container->getExtensionConfig('netgen_social_connect');

        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, $extensionConfig);

        $resourceOwners = !empty($config['resource_owners']) ? $config['resource_owners'] : array();

        foreach ($resourceOwners as $name => $owner) {
            if ($owner['use_config_resolver']) {
                $resourceOwnerDefinition = $container->findDefinition('hwi_oauth.resource_owner.'.$name);
                $resourceOwnerDefinition->addMethodCall('setConfigResolver', array(new Reference('ezpublish.config.resolver')));
            }
        }
    }
}
