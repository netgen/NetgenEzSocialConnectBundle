<?php

namespace Netgen\Bundle\EzSocialConnectBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class NetgenEzSocialConnectExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $processor = new ConfigurationProcessor($container, 'netgen_ez_social_connect');
        $processor->mapConfig(
            $config,
            function ($scopeSettings, $currentScope, ContextualizerInterface $contextualizer)
            {
                $contextualizer->setContextualParameter('user_content_type_identifier', $currentScope, $scopeSettings['user_content_type_identifier']);
                $contextualizer->setContextualParameter('first_name', $currentScope, $scopeSettings['fields']['first_name']);
                $contextualizer->setContextualParameter('last_name', $currentScope, $scopeSettings['fields']['last_name']);
                $contextualizer->setContextualParameter('profile_image', $currentScope, $scopeSettings['fields']['profile_image']);
                $contextualizer->setContextualParameter('fields', $currentScope, $scopeSettings['fields']);
            }
        );

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('parameters.yml');
    }
}
