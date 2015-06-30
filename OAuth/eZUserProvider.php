<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;


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
    public function loadUserByOAuthUserResponse( UserResponseInterface $response )
    {
        $userId = $response->getUsername();
        $login = $response->getNickname() . '-' . $userId; // unique login for ez

        /** @var OAuthEzUser $user */
        $user = new OAuthEzUser( $login , $userId );

        $real_name = $response->getRealName();

        if ( !empty($real_name) )
        {
            $real_name = explode(' ', $real_name );
            if( count($real_name) >= 2 )
            {
                $user->setFirstName( array_shift( $real_name ) );
                $user->setLastName( implode( ' ', $real_name ) );
            }
            else
            {
                $user->setFirstName( $real_name[ 0 ] );
                $user->setLastName( $real_name[ 0 ] );
            }
        }
        else
        {
            $userEmail = $response->getEmail();
            if ( !empty( $userEmail ) )
            {
                $emailArray = explode( '@', $userEmail );
                $user->setFirstName( $emailArray[ 0 ] );
                $user->setLastName( $emailArray[ 0 ] );
            }
            else
            {
                $user->setFirstName( $response->getNickname() );
                $user->setLastName( $response->getResourceOwner()->getName() );
            }
        }

        if ( !$response->getEmail() )
        {
            $email = md5( 'socialbundle' . $response->getResourceOwner()->getName() . $userId ) . '@localhost.local';
            $user->setEmail( $email );
        }
        else
        {
            $user->setEmail( $response->getEmail() );
        }

        $user->setResourceOwnerName( $response->getResourceOwner()->getName() );

        if ( $response->getProfilePicture() )
        {
            $user->setImageLink( $response->getProfilePicture() );
        }

        return $user;
    }
}