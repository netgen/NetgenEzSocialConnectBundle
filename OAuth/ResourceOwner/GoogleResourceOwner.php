<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth\ResourceOwner;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GoogleResourceOwner as BaseGoogleResourceOwner;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GoogleResourceOwner extends BaseGoogleResourceOwner
{
    /** @var  ConfigResolverInterface */
    protected $configResolver;

    /**
     * Sets config resolver and appropriate client id and client secret
     *
     * @param ConfigResolverInterface $configResolver
     */
    public function setConfigResolver( ConfigResolverInterface $configResolver )
    {
        $this->configResolver = $configResolver;

        if ( $this->configResolver->hasParameter( 'google.id', 'netgen_social_connect' ) &&
            $this->configResolver->hasParameter( 'google.secret', 'netgen_social_connect' ) )
        {
            $this->options[ 'client_id' ] = $this->configResolver->getParameter( 'google.id', 'netgen_social_connect' );
            $this->options[ 'client_secret' ] = $this->configResolver->getParameter( 'google.secret', 'netgen_social_connect' );
        }
    }
}