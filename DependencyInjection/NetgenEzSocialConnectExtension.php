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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('parameters.yml');

        $processor = new ConfigurationProcessor($container, 'netgen_ez_social_connect');

        $processor->mapConfig(
            $config,
            function ($scopeSettings, $currentScope, ContextualizerInterface $contextualizer)
            {
                $contextualizer->setContextualParameter('user_content_type_identifier', $currentScope, $scopeSettings['user_content_type_identifier']);
                $contextualizer->setContextualParameter('merge_social_accounts', $currentScope, $scopeSettings['merge_social_accounts']);
            }
        );

        $processor->mapConfigArray('field_identifiers', $config);
    }
}
