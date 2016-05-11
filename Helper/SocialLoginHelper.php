<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzSocialConnectBundle\Exception\MissingConfigurationException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Symfony\Component\Filesystem\Exception\IOException;
use Psr\Log\LoggerInterface;
use eZ\Publish\Core\Helper\FieldHelper;

class SocialLoginHelper
{
    /** @var Repository Repository */
    protected $repository;

    /** @var  EntityManagerInterface */
    protected $entityManager;

    /** @var  ConfigResolverInterface */
    protected $configResolver;

    /** @var  \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var  \eZ\Publish\Core\Helper\FieldHelper */
    protected $fieldHelper;

    /** @var  string */
    protected $firstName;

    /** @var  string */
    protected $lastName;

    /** @var  string */
    protected $imageField;

    /**
     * @param Repository              $repository
     * @param EntityManagerInterface  $entityManager
     * @param ConfigResolverInterface $configResolver
     * @param FieldHelper             $fieldHelper
     * @param LoggerInterface         $logger
     */
    public function __construct(
        Repository $repository,
        EntityManagerInterface $entityManager,
        ConfigResolverInterface $configResolver,
        FieldHelper $fieldHelper,
        LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->configResolver = $configResolver;
        $this->fieldHelper = $fieldHelper;
        $this->logger = $logger;


        $fields = $this->configResolver->getParameter('field_identifiers', 'netgen_ez_social_connect');

        $this->firstName = $fields['first_name'];
        $this->lastName = $fields['last_name'];
        $this->imageField = $fields['profile_image'];
    }

    /**
     * Downloads image from external link to local file system.
     *
     * @param string $imageLink
     *
     * @return null|string
     *
     * @throws IOException if failed to create local directory
     */
    public function downloadExternalImage($imageLink)
    {
        // download image from external link
        $data = file_get_contents($imageLink);

        preg_match("/.+\.(jpg|png|jpeg|gif)/", $imageLink, $imageName);

        if (!empty($imageName[ 0 ])) {
            $storageDir = '/tmp/';
            if (!is_dir($storageDir)) {
                if (!mkdir($storageDir)) {
                    throw new IOException('Failed to create dir', 0, null, $storageDir);
                }
            }
            $imageFileName = $storageDir.basename($imageName[ 0 ]);
        }
        if (!empty($imageFileName) && file_put_contents($imageFileName, $data)) {
            if ($this->logger !== null) {
                $this->logger->notice("Local image created: {$imageFileName}.");
            }

            return $imageFileName;
        } else {
            if ($this->logger !== null) {
                $this->logger->error("Problem while saving image {$imageLink}.");
            }

            return;
        }
    }

    /**
     * Adds profile image to ez user from external link.
     *
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     * @param string                                      $imageLink External link
     */
    public function addProfileImage(User $user, $imageLink)
    {
        $imageFieldIdentifier = $this->imageField;
        if (empty($imageFieldIdentifier)) {
            return;
        }

        if (!$this->fieldHelper->isFieldEmpty($user->content, $imageFieldIdentifier)) {
            return;
        }

        $imageFileName = $this->downloadExternalImage($imageLink);

        $language = $this->configResolver->getParameter('languages');
        $language = $language[0];

        $this->repository->sudo(
            function (Repository $repository) use ($user, $language, $imageFileName, $imageFieldIdentifier) {
                $contentService = $repository->getContentService();
                $userDraft = $contentService->createContentDraft($user->content->versionInfo->contentInfo);
                $userUpdateStruct = $contentService->newContentUpdateStruct();
                $userUpdateStruct->initialLanguageCode = $language;
                $userUpdateStruct->setField($imageFieldIdentifier, $imageFileName);
                $userDraft = $contentService->updateContent($userDraft->versionInfo, $userUpdateStruct);

                $contentService->publishVersion($userDraft->versionInfo);
            }
        );
    }

    /**
     * Adds entry to the table.
     *
     * @param User        $user
     * @param OAuthEzUser $authEzUser
     */
    public function addToTable(User $user, OAuthEzUser $authEzUser)
    {
        $OAuthEzEntity = new OAuthEz();
        $OAuthEzEntity
            ->setEzUserId($user->id)
            ->setResourceUserId($authEzUser->getOriginalId())
            ->setResourceName($authEzUser->getResourceOwnerName());

        $this->entityManager->persist($OAuthEzEntity);
        $this->entityManager->flush();
    }

    /**
     * Removes entry from the table.
     *
     * @param OAuthEz $userEntity
     */
    public function removeFromTable(OAuthEz $userEntity)
    {
        $this->entityManager->remove($userEntity);
        $this->entityManager->flush();
    }

    /**
     * Loads from table by OAuthEzUser entity.
     *
     * @param OAuthEzUser $oauthUser
     *
     * @return null|OAuthEz
     */
    public function loadFromTable(OAuthEzUser $oauthUser)
    {
        $results =
            $this->entityManager
                ->getRepository('NetgenEzSocialConnectBundle:OAuthEz')
                ->findBy(
                    array(
                        'resourceUserId' => $oauthUser->getOriginalId(),
                        'resourceName' => $oauthUser->getResourceOwnerName(),
                    ),
                    array(
                        'ezUserId' => 'DESC', //get last inserted
                    )
                );

        if (!is_array($results) || empty($results)) {
            return;
        }

        return $results[ 0 ];
    }

    /**
     * Loads from table by ez user id and resource name.
     *
     * @param $ezUserId
     * @param $resourceOwnerName
     *
     * @return null|OAuthEzUser
     */
    public function loadFromTableByEzId($ezUserId, $resourceOwnerName)
    {
        $results =
            $this->entityManager
                ->getRepository('NetgenEzSocialConnectBundle:OAuthEz')
                ->findBy(
                    array(
                        'ezUserId' => $ezUserId,
                        'resourceName' => $resourceOwnerName,
                    )
                );

        if (!is_array($results) || empty($results)) {
            return;
        }

        return $results[ 0 ];
    }

    /**
     * Creates ez user from OAuthEzUser entity.
     *
     * @param OAuthEzUser $oauthUser
     *
     * @return User
     *
     * @throws MissingConfigurationException if user group parameter is not set up
     */
    public function createEzUser(OAuthEzUser $oauthUser)
    {
        $userService = $this->repository->getUserService();

        $loginId = $oauthUser->getOriginalId();
        $username = $oauthUser->getUsername();
        $password = md5($loginId.$username);
        $first_name = $oauthUser->getFirstName();
        $last_name = $oauthUser->getLastName();
        $imageLink = $oauthUser->getImagelink();

        $contentTypeIdentifier = $this->configResolver->getParameter('user_content_type_identifier', 'netgen_ez_social_connect');
        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);
        $languages = $this->configResolver->getParameter('languages');

        $userCreateStruct = $userService->newUserCreateStruct(
            $username,
            $oauthUser->getEmail(),
            $password,
            $languages[ 0 ],
            $contentType
        );

        if (!empty($first_name)) {
            if (!empty($this->firstName)) {
                $userCreateStruct->setField($this->firstName, $first_name);
            }
        }
        if (!empty($last_name)) {
            if (!empty($this->lastName)) {
                $userCreateStruct->setField($this->lastName, $last_name);
            }
        }

        $imageFileName = null;

        $imageFieldIdentifier = $this->imageField;

        if (!empty($imageLink) && !empty($imageFieldIdentifier)) {
            $imageFileName = $this->downloadExternalImage($imageLink);
            $userCreateStruct->setField($imageFieldIdentifier, $imageFileName);
        }

        $userCreateStruct->enabled = true;

        if (!$this->configResolver->hasParameter('oauth.user_group', 'netgen_social_connect')) {
            throw new MissingConfigurationException('oauth.user_group');
        }
        $userGroupIds = $this->configResolver->getParameter('oauth.user_group', 'netgen_social_connect');

        if (empty($userGroupIds[ $oauthUser->getResourceOwnerName() ])) {
            throw new MissingConfigurationException('oauth.user_group.'.$oauthUser->getResourceOwnerName());
        }

        $userGroupId = $userGroupIds[$oauthUser->getResourceOwnerName()];
        $newUser = $this->repository->sudo(
            function (Repository $repository) use ($userCreateStruct, $userGroupId) {
                $userGroup = $repository->getUserService()->loadUserGroup($userGroupId);

                return $repository->getUserService()->createUser(
                    $userCreateStruct,
                    array($userGroup)
                );
            }
        );

        return $newUser;
    }

    /**
     * Updates ez user fields.
     *
     * @param User  $user
     * @param array $fields
     */
    public function updateUserFields(User $user, array $fields)
    {
        try {
            $userUpdateStruct = $this->repository->getUserService()->newUserUpdateStruct();
            foreach ($fields as $name => $value) {
                $userUpdateStruct->$name = $value;
            }

            $this->repository->sudo(
                function (Repository $repository) use ($user, $userUpdateStruct) {
                    return $repository->getUserService()->updateUser($user, $userUpdateStruct);
                }
            );
        } catch (\Exception $e) {
            // fail silently - just create a log
            if ($this->logger !== null) {
                $fieldNames = array_keys($fields);
                $fieldNamesString = implode(', ', $fieldNames);

                $this->logger->error("SocialConnect - failed to update fields '{$fieldNamesString}' on user with id {$user->id}");
            }
        }
    }

    /**
     * Loads ez user from the repository.
     *
     * @param $userId
     *
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function loadEzUserById($userId)
    {
        return $this->repository->getUserService()->loadUser($userId);
    }
}
