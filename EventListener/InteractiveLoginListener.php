<?php

namespace Netgen\Bundle\EzSocialConnectBundle\EventListener;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ChainConfigResolver;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\Helper\FieldHelper;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;

class InteractiveLoginListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository $repository
     */
    protected $repository;

    /** @var  \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver $configResolver */
    protected $configResolver;

    /** @var int $userGroup */
    protected $userGroup;

    /** @var  FieldHelper */
    protected $fieldHelper;

    /** @var  EntityManagerInterface */
    protected $entityManager;

    public function __construct(
        Repository $repository,
        ChainConfigResolver $configResolver,
        $userGroup,
        FieldHelper $fieldHelper,
        EntityManagerInterface $entityManager
    )
    {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->userGroup = $userGroup;
        $this->fieldHelper = $fieldHelper;
        $this->entityManager = $entityManager;
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

        /** @var \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $oauthUser */
        $oauthUser = $event->getAuthenticationToken()->getUser();

        $loginId = $oauthUser->getOriginalId();
        $username = $oauthUser->getUsername();
        $password = md5( $loginId . $username );
        $email = $oauthUser->getEmail();
        $first_name = $oauthUser->getFirstName();
        $last_name = $oauthUser->getLastName();
        $resourceOwnerName = $oauthUser->getResourceOwnerName();
        $imageLink = $oauthUser->getImagelink();

        $OAuthEzEntity =
            $this->entityManager
                ->getRepository( 'NetgenEzSocialConnectBundle:OAuthEz' )
                ->findBy(
                    array(
                        'resourceUserId' => $loginId,
                        'resourceName' => $resourceOwnerName
                    ),
                    array(
                        'ezUserId' => 'DESC' //get last inserted
                    )
                );

        if( !empty( $OAuthEzEntity ) )
        {
            try
            {
                /** @var User $user */
                $user = $userService->loadUser( $OAuthEzEntity[ 0 ]->getEzUserId() );

                if ( $this->fieldHelper->isFieldEmpty( $user->content, "image" ) && !empty( $imageLink ) )
                {
                    $this->addProfileImage( $user, $imageLink );
                }

                if( $email !== $user->email )
                {
                    try
                    {
                        $this->repository->setCurrentUser(
                            $userService->loadUserByLogin("admin")
                        );

                        $userUpdateStruct = $userService->newUserUpdateStruct();
                        $userUpdateStruct->email = $email;
                        $userService->updateUser( $user, $userUpdateStruct );

                        $this->repository->setCurrentUser( $user );
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
                // fallback to creating new user
            }
        }

        $this->repository->setCurrentUser(
            $userService->loadUserByLogin( "admin" )
        );

        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier( "user" );
        $languages = $this->configResolver->getParameter( 'languages' );

        $userCreateStruct = $userService->newUserCreateStruct(
            $username,
            $email,
            $password,
            $languages[ 0 ],
            $contentType
        );
        if ( !empty( $first_name ) )
        {
            $userCreateStruct->setField( 'first_name', $first_name );
        }
        if ( !empty( $last_name ) )
        {
            $userCreateStruct->setField( 'last_name', $last_name );
        }

        $imageFileName = null;
        if ( !empty( $imageLink ) )
        {
            $imageFileName = $this->downloadExternalImage( $imageLink );

            // upload image to ez
            $userCreateStruct->setField( 'image', $imageFileName );
        }

        // Created user needs to be enabled
        $userCreateStruct->enabled = true;

        $userGroup = $userService->loadUserGroup( $this->userGroup[ $resourceOwnerName ] );

        $user = $userService->createUser(
            $userCreateStruct,
            array( $userGroup )
        );

        // add to table
        $OAuthEzEntity = new OAuthEz();
        $OAuthEzEntity
            ->setEzUserId( $user->id )
            ->setResourceUserId( $loginId )
            ->setResourceName( $resourceOwnerName );

        $this->entityManager->persist( $OAuthEzEntity );
        $this->entityManager->flush();

        $this->repository->setCurrentUser( $user );
        $event->setApiUser( $user );
    }

    protected function downloadExternalImage( $imageLink )
    {
        // download image from facebook
        $data = file_get_contents( $imageLink );

        preg_match("/.+\.(jpg|png|jpeg|gif)/", $imageLink, $imageName );

        if( !empty($imageName[0]) )
        {
            $storageDir = '/tmp/';
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

            return $imageFileName;
        }
        else
        {
            \eZLog::write( 'Problem while saving image ' . $imageLink );

            return null;
        }
    }

    protected function addProfileImage( $user, $imageLink )
    {
        $userService = $this->repository->getUserService();

        $this->repository->setCurrentUser(
            $userService->loadUserByLogin("admin")
        );
        $imageFileName = $this->downloadExternalImage( $imageLink );
        $contentService = $this->repository->getContentService();

        $languages = $this->configResolver->getParameter( 'languages' );
        $userDraft = $contentService->createContentDraft( $user->content->versionInfo->contentInfo );
        $userUpdateStruct = $contentService->newContentUpdateStruct();
        $userUpdateStruct->initialLanguageCode = $languages[0];
        $userUpdateStruct->setField( 'image', $imageFileName );
        $userDraft = $contentService->updateContent( $userDraft->versionInfo, $userUpdateStruct );

        $contentService->publishVersion( $userDraft->versionInfo );
    }
} 