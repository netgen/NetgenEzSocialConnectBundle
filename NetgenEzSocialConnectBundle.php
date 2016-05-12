<?php

namespace Netgen\Bundle\EzSocialConnectBundle;

use Netgen\Bundle\EzSocialConnectBundle\DependencyInjection\NetgenEzSocialConnectExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Netgen\Bundle\EzSocialConnectBundle\DependencyInjection\Compiler\AddConfigResolverCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class NetgenEzSocialConnectBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddConfigResolverCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }

    /**
     * @return NetgenEzSocialConnectExtension
     */
    public function getContainerExtension()
    {
        return new NetgenEzSocialConnectExtension();
    }
}
