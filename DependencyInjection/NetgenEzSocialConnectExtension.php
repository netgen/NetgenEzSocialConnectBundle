<?php

namespace Netgen\Bundle\EzSocialConnectBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class NetgenEzSocialConnectExtension extends Extension implements PrependExtensionInterface
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

        $processor = new ConfigurationProcessor($container, 'netgen_social_connect');

        $processor->mapConfig(
            $config,
            function ($scopeSettings, $currentScope, ContextualizerInterface $contextualizer)
            {
                $contextualizer->setContextualParameter('user_content_type_identifier', $currentScope, $scopeSettings['user_content_type_identifier']);
                $contextualizer->setContextualParameter('merge_accounts', $currentScope, $scopeSettings['merge_accounts']);

                $fieldIdentifiers = $scopeSettings['field_identifiers'];
                if (!empty($fieldIdentifiers['first_name'])) {
                    $contextualizer->setContextualParameter('first_name', $currentScope, $fieldIdentifiers['first_name']);
                }
                if (!empty($fieldIdentifiers['last_name'])) {
                    $contextualizer->setContextualParameter('last_name', $currentScope, $fieldIdentifiers['last_name']);
                }
                if (!empty($fieldIdentifiers['profile_image'])) {
                    $contextualizer->setContextualParameter('profile_image', $currentScope, $fieldIdentifiers['profile_image']);
                }
            }
        );

        $processor->mapConfigArray('field_identifiers', $config);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'netgen_social_connect';
    }

    /**
     * Prepend configuration for resource owners used in config.yml
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
    }
}
