<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Twig;

use eZ\Publish\Core\MVC\Symfony\Security\UserInterface;
use Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Twig extension to allow checking whether the current user is connected to a given social resource owner.
 */
class SocialConnectExtension extends \Twig_Extension
{
    /**
     * @var \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper
     */
    protected $socialLoginHelper;

    /**
     * SocialConnectExtension constructor.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper $socialLoginHelper
     */
    public function __construct(SocialLoginHelper $socialLoginHelper)
    {
        $this->socialLoginHelper = $socialLoginHelper;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('is_user_linked', array($this, 'isConnectedToOwner')),
        );
    }

    /**
     * Checks whether the current user is logged in to a given resource owner
     *
     * @param \eZ\Publish\Core\MVC\Symfony\Security\UserInterface $user
     * @param string $resourceOwnerName
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function isConnectedToOwner(UserInterface $user, $resourceOwnerName)
    {
        if ($user instanceof UserInterface){
            return !empty($this->socialLoginHelper->loadFromTableByEzId($user->getAPIUser()->id, $resourceOwnerName));
        }

        throw new \InvalidArgumentException('User must implement \eZ\Publish\Core\MVC\Symfony\Security\UserInterface');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'netgen_social_connect_extension';
    }
}
