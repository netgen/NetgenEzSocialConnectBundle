<?php

namespace Netgen\Bundle\EzSocialConnectBundle\DependencyInjection;

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

        $container->setParameter('netgen_ez_social_connect', $config);

        foreach($config as $key => $value){
            $concatenatedParam = 'netgen_ez_social_connect.'.$key;
            $container->setParameter($concatenatedParam, $value);

            if (is_array($value)) {
                foreach($value as $subKey => $subValue) {
                    $container->setParameter($concatenatedParam.'.'.$subKey, $subValue);
                }
            }
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('parameters.yml');
    }
}
