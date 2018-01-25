<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth\ResourceOwner;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\TwitterResourceOwner as BaseTwitterResourceOwner;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class TwitterResourceOwner extends BaseTwitterResourceOwner
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

        if ($this->configResolver->hasParameter('twitter.id', 'netgen_social_connect') &&
            $this->configResolver->hasParameter('twitter.secret', 'netgen_social_connect')) {
            $this->options['client_id'] = $this->configResolver->getParameter('twitter.id', 'netgen_social_connect');
            $this->options['client_secret'] = $this->configResolver->getParameter('twitter.secret', 'netgen_social_connect');
        }
    }
}
