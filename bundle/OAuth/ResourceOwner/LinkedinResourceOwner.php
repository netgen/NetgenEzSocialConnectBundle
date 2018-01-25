<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth\ResourceOwner;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\LinkedinResourceOwner as BaseLinkedinResourceOwner;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class LinkedinResourceOwner extends BaseLinkedinResourceOwner
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

        if ($this->configResolver->hasParameter('linkedin.id', 'netgen_social_connect') &&
            $this->configResolver->hasParameter('linkedin.secret', 'netgen_social_connect')) {
            $this->options['client_id'] = $this->configResolver->getParameter('linkedin.id', 'netgen_social_connect');
            $this->options['client_secret'] = $this->configResolver->getParameter('linkedin.secret', 'netgen_social_connect');
        }
    }
}
