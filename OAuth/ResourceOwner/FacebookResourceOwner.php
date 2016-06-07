<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth\ResourceOwner;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\FacebookResourceOwner as BaseFacebookResourceOwner;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class FacebookResourceOwner extends BaseFacebookResourceOwner
{
    /** @var  ConfigResolverInterface */
    protected $configResolver;

    /**
     * Sets config resolver and appropriate client id and client secret.
     *
     * @param ConfigResolverInterface $configResolver
     */
    public function setConfigResolver(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;

        if ($this->configResolver->hasParameter('facebook.id', 'netgen_social_connect') &&
            $this->configResolver->hasParameter('facebook.secret', 'netgen_social_connect')) {
            $this->options['client_id'] = $this->configResolver->getParameter('facebook.id', 'netgen_social_connect');
            $this->options['client_secret'] = $this->configResolver->getParameter('facebook.secret', 'netgen_social_connect');
        }
    }
}
