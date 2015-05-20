<?php

namespace Netgen\Bundle\EzSocialConnectBundle\EventListener;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ChainConfigResolver;


class InteractiveLoginListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository $repository
     */
    private $repository;

    /** @var  \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver $configResolver */
    private $configResolver;

    /** @var int $userGroup */
    private $userGroup;

    public function __construct( Repository $repository,
                                 ChainConfigResolver $configResolver,
                                 $userGroup)
    {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->userGroup = $userGroup;
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin'
        );
    }

    /**
     * Authenticates external user, or creates new eZ user if one does not already exist
     *
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin( InteractiveLoginEvent $event )
    {
        $userService = $this->repository->getUserService();

        /** @var \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $user */
        $oauthUser = $event->getAuthenticationToken()->getUser();

        $username = $oauthUser->getUsername();
        if ( empty( $username ) )
        {
            $username = $oauthUser->getFirstName();
        }
        $password = md5( $username );
        $email = $oauthUser->getEmail();
        $first_name = $oauthUser->getFirstName();
        $last_name = $oauthUser->getLastName();
        $resourceOwner = $oauthUser->getResourceOwner();
        $imageLink = $oauthUser->getImagelink();

        // try to load existing user by email (if more than one, return the first one)
        $user = $userService->loadUsersByEmail( $email );
        if ( is_array($user) && count($user)>0 )
            $event->setApiUser( $user[0] );
        else
        {
            $this->repository->setCurrentUser(
                $userService->loadUserByLogin("admin")
            );

            $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier("user");
            $languages = $this->configResolver->getParameter( 'languages' );

            $userCreateStruct = $userService->newUserCreateStruct(
                $username,
                $email,
                $password,
                $languages[0],
                $contentType
            );
            if ( !empty($first_name) )
                $userCreateStruct->setField('first_name', $first_name);
            if ( !empty($last_name) )
                $userCreateStruct->setField('last_name', $last_name);

            $imageFileName = null;
            if( !empty($imageLink) )
            {
                // download image from facebook
                $data = file_get_contents( $imageLink );

                preg_match("/.+\.(jpg|png|jpeg|gif)/", $imageLink, $imageName );

                if( !empty($imageName[0]) )
                {
                    $storageDir = 'var/kavli/storage/social/';
                    if( !is_dir( $storageDir ) )
                    {
                        if( !mkdir( $storageDir ) )
                        {
                            throw new \Exception('Failed to create dir');
                        }
                    }
                    $imageFileName = $storageDir . basename( $imageName[ 0 ] );
                }
                if( !empty( $imageFileName ) && file_put_contents( $imageFileName, $data ) )
                {
                    \eZLog::write( 'Local image created ' . $imageFileName );
                }
                else
                {
                    \eZLog::write( 'Problem while saving image ' . $imageLink );
                }
                // upload image to ez
                $userCreateStruct->setField( 'image', $imageFileName );
            }

            // Created user needs to be enabled
            $userCreateStruct->enabled = true;

            $userGroup = $userService->loadUserGroup($this->userGroup[$resourceOwner]);

            $user = $userService->createUser(
                $userCreateStruct,
                array($userGroup)
            );

            $event->setApiUser($user);
        }
    }
} 