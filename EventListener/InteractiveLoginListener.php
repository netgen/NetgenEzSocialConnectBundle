<?php

namespace Netgen\Bundle\EzSocialConnectBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;

class InteractiveLoginListener implements EventSubscriberInterface
{

    /** @var  \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /** @var \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper */
    protected $loginHelper;

    /** @var  \Symfony\Component\HttpFoundation\Session\SessionInterface */
    protected $session;

    /**
     * InteractiveLoginListener constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper $loginHelper
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SocialLoginHelper $loginHelper,
        SessionInterface $session
    )
    {
        $this->entityManager = $entityManager;
        $this->loginHelper = $loginHelper;
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin'
        );
    }

    /**
     * Authenticates external user, or creates new eZ user if one does not already exist
     * If user id is in session, connect ez user to external one
     *
     * @param \eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent $event
     */
    public function onInteractiveLogin( InteractiveLoginEvent $event )
    {
        /** @var \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $oauthUser */
        $oauthUser = $event->getAuthenticationToken()->getUser();

        if ( $this->session->has( 'social_connect_ez_user_id' ) )
        {
            //there is user id in session, it means we have to connect the user to it
            $connectEzId = $this->session->get( 'social_connect_ez_user_id' );
            $this->session->remove( 'social_connect_ez_user_id' );

            $ezUser = $this->loginHelper->loadEzUserById( $connectEzId );
            $this->loginHelper->addToTable( $ezUser, $oauthUser );

            $event->setApiUser( $ezUser );

            return;
        }

        /** @var \Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz $oauthEzUserEntity */
        $oauthEzUserEntity = $this->loginHelper->loadFromTable( $oauthUser );

        if ( !empty( $oauthEzUserEntity ) )
        {
            try
            {
                $ezUserId = $oauthEzUserEntity->getEzUserId();
                $user = $this->loginHelper->loadEzUserById( $ezUserId );

                $imageLink = $oauthUser->getImageLink();
                if ( !empty( $imageLink ) )
                {
                    $this->loginHelper->addProfileImage( $user, $imageLink );
                }

                if ( $oauthUser->getEmail() !== $user->email && !strpos(strrev( $oauthUser->getEmail() ), 'lacol.tsohlacol') === 0 )
                {
                    $this->loginHelper->updateUserFields( $user, array( "email" => $oauthUser->getEmail() ) );
                }

                $event->setApiUser( $user );

                return;
            }
            catch ( NotFoundException $e )
            {
                // something went wrong - data is in the table, but the user does not exist
                // remove falty data and fallback to creating new user
                $this->loginHelper->removeFromTable( $oauthEzUserEntity );
            }
        }

        $user = $this->loginHelper->createEzUser( $oauthUser );
        $this->loginHelper->addToTable( $user, $oauthUser );
        $event->setApiUser( $user );
    }
}
