<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use eZ\Publish\Core\MVC\Symfony\Security\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ChainConfigResolver;


class eZUserProvider implements OAuthAwareUserProviderInterface
{
    /**
     * Loads the user by a given UserResponseInterface object.
     *
     * @param UserResponseInterface $response
     *
     * @return OAuthEzUser
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        /** @var OAuthEzUser $user */
        $user = new OAuthEzUser( $response->getNickname() );

        $real_name = $response->getRealName();

        if ( !empty($real_name) )
        {
            $real_name = explode(' ', $real_name );
            if( count($real_name) >= 2 )
            {
                $user->setFirstName( $real_name[0] );
                $user->setLastName( $real_name[1] );
            }
            else
            {
                $user->setFirstName( $real_name[0] );
                $user->setLastName( '' );
            }
        }
        if ( !$response->getEmail() )
        {

            $email = md5( 'socialbundle' . $response->getResourceOwner()->getName() . '_' . $response->getResponse()['id_str'] ) . '@localhost.local';
            $user->setEmail( $email );
        }
        else
        {
            $user->setEmail( $response->getEmail() );
        }

        $user->setResourceOwner( $response->getResourceOwner()->getName() );
        return $user;
    }
}