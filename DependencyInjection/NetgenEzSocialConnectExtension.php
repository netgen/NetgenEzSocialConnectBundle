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

                if (array_key_exists('field_identifiers', $scopeSettings)) {
                    foreach ($scopeSettings['field_identifiers'] as $fieldIdentifierName => $fieldIdentifierValue) {
                        $contextualizer->setContextualParameter($fieldIdentifierName, $currentScope, $fieldIdentifierValue);
                    }
                }
                if (array_key_exists('oauth_user_group', $scopeSettings)) {
                    foreach ($scopeSettings['oauth_user_group'] as $userGroupName => $userGroupId) {
                        $contextualizer->setContextualParameter('oauth_user_group.' . $userGroupName, $currentScope, $userGroupId);
                    }
                }
            }
        );
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
