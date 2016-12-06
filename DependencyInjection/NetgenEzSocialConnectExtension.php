<?php

namespace Netgen\Bundle\EzSocialConnectBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class NetgenEzSocialConnectExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Stores HWI resource owner data if it's not dynamically set.
     * Example content: ['facebook' => ['id' => '12345678', 'secret' => '12345678' ]]
     *
     * @var array
     */
    private $hwiFallbackData = array();

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
                if (array_key_exists('resource_owners', $scopeSettings)) {
                    foreach ($scopeSettings['resource_owners'] as $resourceOwnerName => $resourceOwnerData) {

                        if (!empty($resourceOwnerData['id'])) {
                            $contextualizer->setContextualParameter("$resourceOwnerName.id", $currentScope, $resourceOwnerData['id']);
                        } else if (!empty($this->hwiFallbackData[$resourceOwnerName]['id'])) {
                            $contextualizer->setContextualParameter("$resourceOwnerName.id", $currentScope, $this->hwiFallbackData[$resourceOwnerName]['id']);
                        }

                        if (!empty($resourceOwnerData['secret'])) {
                            $contextualizer->setContextualParameter("$resourceOwnerName.secret", $currentScope, $resourceOwnerData['secret']);
                        } else if (!empty($this->hwiFallbackData[$resourceOwnerName]['secret'])) {
                            $contextualizer->setContextualParameter("$resourceOwnerName.secret", $currentScope, $this->hwiFallbackData[$resourceOwnerName]['secret']);
                        }

                        if (!empty($resourceOwnerData['user_group'])) {
                            $contextualizer->setContextualParameter("$resourceOwnerName.user_group", $currentScope, $resourceOwnerData['user_group']);
                        }

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
     * Prepend configuration for resource owners and doctrine orm used in config.yml
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $configFile = __DIR__.'/../Resources/config/doctrine.yml';
        $config = Yaml::parse(file_get_contents($configFile));
        $container->prependExtensionConfig('doctrine', $config);
        $container->addResource(new FileResource($configFile));

        $this->addDefaultParameters($container);
        $this->setHwiFallbackData($container);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
    }

    /**
     * If there are no id/secret parameters defined under the netgen_social_connect -> resource_owners,
     * fetch the id/secret data directly from the hwi_oauth configuration.
     *
     * @param ContainerBuilder $container
     */
    private function setHwiFallbackData(ContainerBuilder $container)
    {
        $extensionConfig = $container->getExtensionConfig('hwi_oauth');
        $extensionConfig = reset($extensionConfig);

        foreach ($extensionConfig['resource_owners'] as $resourceOwnerName => $resourceOwnerValues)
        {
            $this->hwiFallbackData[$resourceOwnerName] = array();

            // Do nothing if hwi configuration has dynamic parameters (i.e. we are using siteaccess-aware configuration)
            if (mb_strpos('%', $resourceOwnerValues['client_id']) === false) {
                $this->hwiFallbackData[$resourceOwnerName]['id'] = $resourceOwnerValues['client_id'];
            }

            if (mb_strpos('%', $resourceOwnerValues['client_secret']) === false) {
                $this->hwiFallbackData[$resourceOwnerName]['secret'] = $resourceOwnerValues['client_secret'];
            }
        }
    }

    /**
     * Adds default settings required for siteaccess-aware configuration.
     * See: https://doc.ez.no/display/EZP/How+to+expose+SiteAccess+aware+configuration+for+your+bundle
     *
     * @param ContainerBuilder $container
     */
    private function addDefaultParameters(ContainerBuilder $container)
    {
        foreach (array('facebook', 'twitter', 'linkedin', 'google') as $resourceOwnerName) {
            foreach (array('id' => 'DEFAULT', 'secret' => 'DEFAULT', 'user_group' => 11) as $parameterName => $parameterValue) {
                $container->setParameter("netgen_social_connect.default.{$resourceOwnerName}.{$parameterName}", $parameterValue);
            }
        }

        $container->setParameter('netgen_social_connect.default.first_name', 'first_named');
        $container->setParameter('netgen_social_connect.default.last_name', 'last_name');
        $container->setParameter('netgen_social_connect.default.profile_image', 'image');
    }
}
