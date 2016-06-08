<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Twig;

use eZ\Publish\Core\MVC\Symfony\Security\User;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper;

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
            new \Twig_SimpleFunction('is_user_connected', array($this, 'isConnectedToOwner')),
            new \Twig_SimpleFunction('is_user_disconnectable', array($this, 'isDisconnectable')),
        );
    }

    /**
     * Checks whether the current user is linked to a given resource owner
     *
     * @param int    $userId
     * @param string $resourceOwnerName
     *
     * @return bool
     */
    public function isConnectedToOwner($userId, $resourceOwnerName)
    {
        $user = $this->socialLoginHelper->loadFromTableByEzId($userId, $resourceOwnerName);

        return $user instanceof OAuthEz;
    }

    /**
     * Checks whether a given social link is disconnectable.
     *
     * If an eZ user was initially created using a social login,
     * this can be used to prevent that table entry from being deleted or the 'disconnect' button being shown.
     *
     * @param int  $userId
     * @param bool $resourceOwnerName
     *
     * @return bool
     */
    public function isDisconnectable($userId, $resourceOwnerName)
    {
        $ezUser = $this->socialLoginHelper->loadFromTableByEzId($userId, $resourceOwnerName);

        if ($ezUser instanceof OAuthEz) {
            return $ezUser->isDisconnectable();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'netgen_social_connect_extension';
    }
}
