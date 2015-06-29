<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzSocialConnectBundle\Exception\MissingConfigurationException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;

class SocialLoginHelper
{
    /** @var Repository Repository */
    protected $repository;

    /** @var  EntityManagerInterface */
    protected $entityManager;

    /** @var  ConfigResolverInterface */
    protected $configResolver;


    public function __construct(
        Repository $repository,
        EntityManagerInterface $entityManager,
        ConfigResolverInterface $configResolver
    )
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->configResolver = $configResolver;
    }

    public function downloadExternalImage( $imageLink )
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

    public function addProfileImage( $user, $imageLink )
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

    public function addToTable( User $user, OAuthEzUser $authEzUser )
    {
        $OAuthEzEntity = new OAuthEz();
        $OAuthEzEntity
            ->setEzUserId( $user->id )
            ->setResourceUserId( $authEzUser->getOriginalId() )
            ->setResourceName( $authEzUser->getResourceOwnerName() );

        $this->entityManager->persist( $OAuthEzEntity );
        $this->entityManager->flush();
    }

    public function removeFromTable( OAuthEz $userEntity )
    {
        $this->entityManager->remove( $userEntity );
        $this->entityManager->flush();
    }

    public function loadFromTable( OAuthEzUser $oauthUser )
    {
        $results =
            $this->entityManager
                ->getRepository( 'NetgenEzSocialConnectBundle:OAuthEz' )
                ->findBy(
                    array(
                        'resourceUserId' => $oauthUser->getOriginalId(),
                        'resourceName' => $oauthUser->getResourceOwnerName()
                    ),
                    array(
                        'ezUserId' => 'DESC' //get last inserted
                    )
                );

        if( !is_array( $results ) || empty( $results ) )
        {
            return null;
        }

        return $results[0];
    }

    public function createEzUser( OAuthEzUser $oauthUser )
    {
        $userService = $this->repository->getUserService();

        $this->repository->setCurrentUser(
            $userService->loadUserByLogin( "admin" )
        );

        $loginId = $oauthUser->getOriginalId();
        $username = $oauthUser->getUsername();
        $password = md5( $loginId . $username );
        $first_name = $oauthUser->getFirstName();
        $last_name = $oauthUser->getLastName();
        $imageLink = $oauthUser->getImagelink();

        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier( "user" );
        $languages = $this->configResolver->getParameter( 'languages' );

        $userCreateStruct = $userService->newUserCreateStruct(
            $username,
            $oauthUser->getEmail(),
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
            $userCreateStruct->setField( 'image', $imageFileName );
        }

        $userCreateStruct->enabled = true;

        if( !$this->configResolver->hasParameter( 'oauth.user_group', 'netgen_social_connect' ) )
        {
            throw new MissingConfigurationException( 'oauth.user_group' );
        }
        $userGroupIds = $this->configResolver->getParameter( 'oauth.user_group', 'netgen_social_connect' );

        if( empty( $userGroupIds[ $oauthUser->getResourceOwnerName() ] ) )
        {
            throw new MissingConfigurationException( 'oauth.user_group.' . $oauthUser->getResourceOwnerName()  );
        }

        $userGroup = $userService->loadUserGroup( $userGroupIds[ $oauthUser->getResourceOwnerName() ] );

        $user = $userService->createUser(
            $userCreateStruct,
            array( $userGroup )
        );

        $this->repository->setCurrentUser( $user );

        return $user;
    }

    public function updateUserFields( User $user, $fields )
    {
        $userService = $this->repository->getUserService();

        $this->repository->setCurrentUser(
            $this->repository->getUserService()->loadUserByLogin("admin")
        );

        $userUpdateStruct = $userService->newUserUpdateStruct();
        foreach( $fields as $name => $value )
        {
            $userUpdateStruct->$name = $value;
        }
        $userService->updateUser( $user, $userUpdateStruct );

        $this->repository->setCurrentUser( $user );
    }

    public function loadEzUserById( $userId )
    {
        return $this->repository->getUserService()->loadUser( $userId );
    }
}