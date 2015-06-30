<?php

namespace Netgen\Bundle\EzSocialConnectBundle\EventListener;

use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\Helper\FieldHelper;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class InteractiveLoginListener implements EventSubscriberInterface
{
    /** @var  FieldHelper */
    protected $fieldHelper;

    /** @var  EntityManagerInterface */
    protected $entityManager;

    /** @var SocialLoginHelper */
    protected $loginHelper;

    /** @var  SessionInterface */
    protected $session;

    public function __construct(
        FieldHelper $fieldHelper,
        EntityManagerInterface $entityManager,
        SocialLoginHelper $loginHelper,
        SessionInterface $session
    )
    {
        $this->fieldHelper = $fieldHelper;
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
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin( InteractiveLoginEvent $event )
    {
        if( $this->session->has( 'social_connect_ez_user_id' ) )
        {
            //there is user id in session, it means we have to connect the user to it
            $connectEzId = $this->session->get( 'social_connect_ez_user_id' );
            $this->session->remove( 'social_connect_ez_user_id' );

            $ezUser = $this->loginHelper->loadEzUserById( $connectEzId );
            /** @var OAuthEzUser $oauthUser */
            $oauthUser = $event->getAuthenticationToken()->getUser();

            $this->loginHelper->addToTable( $ezUser, $oauthUser );

            $event->setApiUser( $ezUser );

            return;
        }

        /** @var OAuthEzUser $oauthUser */
        $oauthUser = $event->getAuthenticationToken()->getUser();
        $imageLink = $oauthUser->getImageLink();

        /** @var OAuthEz $oauthEzUserEntity */
        $oauthEzUserEntity = $this->loginHelper->loadFromTable( $oauthUser );

        if( !empty( $oauthEzUserEntity ) )
        {
            try
            {
                $ezUserId = $oauthEzUserEntity->getEzUserId();
                /** @var User $user */
                $user = $this->loginHelper->loadEzUserById( $ezUserId );

                if ( $this->fieldHelper->isFieldEmpty( $user->content, "image" ) &&
                    !empty( $imageLink ) )
                {
                    $this->loginHelper->addProfileImage( $user, $imageLink );
                }

                if( $oauthUser->getEmail() !== $user->email && !strpos(strrev( $oauthUser->getEmail() ), 'lacol.tsohlacol') === 0 )
                {
                    try
                    {
                        $this->loginHelper->updateUserFields( $user, array( "email" => $oauthUser->getEmail() ) );
                    }
                    catch( \Exception $e )
                    {
                        // fail silently - just create a log
                        \eZLog::write( 'ERROR - SocialConnect - failed to update email on user with id ' . $user->id );
                    }
                }

                $event->setApiUser( $user );

                return;
            }
            catch( NotFoundException $e )
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