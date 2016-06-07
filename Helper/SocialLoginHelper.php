<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzSocialConnectBundle\Exception\MissingConfigurationException;
use Netgen\Bundle\EzSocialConnectBundle\Exception\ResourceOwnerNotSupportedException;
use Netgen\Bundle\EzSocialConnectBundle\Exception\UserNotConnectedException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Symfony\Component\Filesystem\Exception\IOException;
use Psr\Log\LoggerInterface;
use eZ\Publish\Core\Helper\FieldHelper;

class SocialLoginHelper
{
    /** @var \eZ\Publish\API\Repository\Repository Repository */
    protected $repository;

    /** @var  \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /** @var  \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    /** @var  \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var  \eZ\Publish\Core\Helper\FieldHelper */
    protected $fieldHelper;

    /** @var  string */
    protected $firstNameIdentifier;

    /** @var  string */
    protected $lastNameIdentifier;

    /** @var  string */
    protected $imageFieldIdentifier;

    /** @var  array */
    protected $baseUrls;

    /**
     * @param \eZ\Publish\API\Repository\Repository        $repository
     * @param \Doctrine\ORM\EntityManagerInterface         $entityManager
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \eZ\Publish\Core\Helper\FieldHelper          $fieldHelper
     * @param \Psr\Log\LoggerInterface                     $logger
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
    }

    /**
     * Injected setter
     *
     * @param $firstNameIdentifier
     */
    public function setFirstNameIdentifier($firstNameIdentifier = null){
        $this->firstNameIdentifier = $firstNameIdentifier;
    }

    /**
     * Injected setter
     *
     * @param $lastNameIdentifier
     */
    public function setLastNameIdentifier($lastNameIdentifier = null){
        $this->lastNameIdentifier = $lastNameIdentifier;
    }

    /**
     * Injected setter
     *
     * @param $imageFieldIdentifier
     */
    public function setProfileImageIdentifier($imageFieldIdentifier = null){
        $this->imageFieldIdentifier = $imageFieldIdentifier;
    }

    /**
     * Injected setter
     *
     * @param $baseUrls
     */
    public function setBaseUrls($baseUrls = null)
    {
        $this->baseUrls = $baseUrls;
    }

    /**
     * Downloads image from external link to local file system.
     *
     * @param string $imageLink
     *
     * @return null|string
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException if failed to create local directory
     */
    protected function downloadExternalImage($imageLink)
    {
        $data = file_get_contents($imageLink);

        preg_match("/.+\.(jpg|png|jpeg|gif)/", $imageLink, $imageName);

        if (!empty($imageName[0])) {
            $storageDir = '/tmp/';
            if (!is_dir($storageDir)) {
                if (!mkdir($storageDir)) {
                    throw new IOException('Failed to create dir', 0, null, $storageDir);
                }
            }
            $imageFileName = $storageDir.basename($imageName[0]);
        }
        if (!empty($imageFileName) && file_put_contents($imageFileName, $data) !== false) {

            return $imageFileName;
        }

        throw new IOException('Failed to create image.', 0, null, $imageLink);
    }

    /**
     * Adds profile image to ez user from external link.
     *
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     * @param string                                      $imageLink External link
     * @param string|null                                 $language
     *
     * @return bool
     */
    public function addProfileImage(User $user, $imageLink, $language = null)
    {
        $imageFieldIdentifier = $this->imageField;
        if (empty($imageFieldIdentifier)) {
            return false;
        }

        if (!$this->fieldHelper->isFieldEmpty($user->content, $imageFieldIdentifier, $language)) {
            return false;
        }

        try {
            $imageFileName = $this->downloadExternalImage($imageLink);
        } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            $this->logger->error("Problem while saving image {$imageLink}: ".$e->getMessage());

            return false;
        }

        $this->logger->notice("Local image created: {$imageFileName}.");

        if (!$language) {
            $language = $this->configResolver->getParameter('languages');
            $language = $language[0];
        }

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

        return true;
    }

    /**
     * Adds entry to the table.
     *
     * If disconnectable is true, this link can be deleted.
     * Otherwise, it is assumed to be the main social login which created the eZ user initially.
     *
     * @param \eZ\Publish\API\Repository\Values\User\User            $user
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $authEzUser
     * @param bool                                                   $disconnectable
     */
    public function addToTable(User $user, OAuthEzUser $authEzUser, $disconnectable = false)
    {
        $OAuthEzEntity = new OAuthEz();
        $OAuthEzEntity
            ->setEzUserId($user->id)
            ->setResourceUserId($authEzUser->getOriginalId())
            ->setResourceName($authEzUser->getResourceOwnerName())
            ->setDisconnectable($disconnectable);

        $this->entityManager->persist($OAuthEzEntity);
        $this->entityManager->flush();
    }

    /**
     * Removes entry from the table.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz $userEntity
     */
    public function removeFromTable(OAuthEz $userEntity)
    {
        $this->entityManager->remove($userEntity);
        $this->entityManager->flush();
    }


    /**
     * Loads from table by eZ user id and resource name.
     *
     * @param string $ezUserId
     * @param string $resourceOwnerName
     *
     * @return null|\Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    public function loadFromTableByEzId($ezUserId, $resourceOwnerName, $onlyDisconnectable = false)
    {
        return $this->loadFromTableByCriteria(array(
            'ezUserId' => $ezUserId,
            'resourceName' => $resourceOwnerName,
        ), $onlyDisconnectable);
    }

    /**
     * Loads from table by resource user id and resource name.
     *
     * @param string $resourceUserId
     * @param string $resourceOwnerName
     *
     * @return null|\Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    public function loadFromTableByResourceUserId($resourceUserId, $resourceOwnerName, $onlyDisconnectable = false)
    {
        return $this->loadFromTableByCriteria(array(
            'resourceUserId' => $resourceUserId,
            'resourceName' => $resourceOwnerName,
        ), $onlyDisconnectable);
    }

    /**
     * Loads from table by criteria.
     *
     * @param array     $criteria
     * @param bool      $onlyDisconnectable
     *
     * @return null|\Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    protected function loadFromTableByCriteria(array $criteria, $onlyDisconnectable = false)
    {
        if ($onlyDisconnectable) {
            $criteria['disconnectable'] = true;
        }

        $results = $this->entityManager->getRepository('NetgenEzSocialConnectBundle:OAuthEz')->findOneBy(
            $criteria,
            array('ezUserId' => 'DESC')     // Get last inserted item.
        );

        if (!is_array($results) || empty($results)) {
            return null;
        }

        return $results[0];
    }

    /**
     * Creates ez user from OAuthEzUser entity.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $oauthUser
     *
     * @return \eZ\Publish\API\Repository\Values\User\User
     *
     * @throws \Netgen\Bundle\EzSocialConnectBundle\Exception\MissingConfigurationException if user group parameter is not set up
     */
    public function createEzUser(OAuthEzUser $oauthUser, $language = null)
    {
        $userService = $this->repository->getUserService();

        $loginId = $oauthUser->getOriginalId();
        $username = $oauthUser->getUsername();
        $password = password_hash(str_shuffle($loginId.microtime().$username), PASSWORD_DEFAULT);
        $firstName = $oauthUser->getFirstName();
        $lastName = $oauthUser->getLastName();
        $imageLink = $oauthUser->getImagelink();

        $contentTypeIdentifier = $this->configResolver->getParameter('user_content_type_identifier', 'netgen_social_connect');
        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);

        if (!$language) {
            $languages = $this->configResolver->getParameter('languages');
            $language = $languages[0];
        }

        $userCreateStruct = $userService->newUserCreateStruct($username, $oauthUser->getEmail(), $password, $language, $contentType);

        if (!empty($firstName) && !empty($this->firstNameIdentifier)) {
            $userCreateStruct->setField($this->firstNameIdentifier, $firstName);
        }

        if (!empty($lastName) && !empty($this->lastNameIdentifier)) {
            $userCreateStruct->setField($this->lastNameIdentifier, $lastName);
        }

        $imageFileName = null;

        if (!empty($imageLink) && !empty($this->imageFieldIdentifier)) {
            try {
                $imageFileName = $this->downloadExternalImage($imageLink);
                $userCreateStruct->setField($this->imageFieldIdentifier, $imageFileName);
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                $this->logger->error("Problem while saving image {$imageLink}: ".$e->getMessage());
            }
        }

        $userCreateStruct->enabled = true;

        $userGroupParameter = $oauthUser->getResourceOwnerName().'.user_group';

        if (!$this->configResolver->hasParameter($userGroupParameter, 'netgen_social_connect')) {
            throw new MissingConfigurationException($userGroupParameter);
        }

        $userGroupId = $this->configResolver->getParameter($userGroupParameter, 'netgen_social_connect');

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
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     * @param array                                       $fields
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
     * @param string $userId
     *
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function loadEzUserById($userId)
    {
        return $this->repository->getUserService()->loadUser($userId);
    }

    /**
     * Fetches an array ['resourceOwner' => 'userProfileUrl'].
     * If the resourceName parameter is omitted, returns all userProfileUrls for registered resource owners.
     *
     * @param int           $userId
     * @param string|null   $resourceName
     *
     * @return array
     *
     * @throws \Netgen\Bundle\EzSocialConnectBundle\Exception\ResourceOwnerNotSupportedException
     * @throws \Netgen\Bundle\EzSocialConnectBundle\Exception\UserNotConnectedException
     */
    public function getProfileUrlsByEzUserId($userId, $resourceName = null)
    {
        $profileUrls = array();

        if ($resourceName) {
            if (!array_key_exists($resourceName, $this->baseUrls)) {
                throw new ResourceOwnerNotSupportedException($resourceName);
            }

            $OAuthEz = $this->loadFromTableByEzId($userId, $resourceName);

            if (empty($OAuthEz)) {
                throw new UserNotConnectedException($resourceName);
            }

            $externalId = $OAuthEz->getResourceUserId();

            $profileUrls[$resourceName] = $this->baseUrls[$resourceName].$externalId;

        } else {
            foreach ($this->baseUrls as $resourceName => $resourceBaseUrl) {
                $OAuthEz = $this->loadFromTableByEzId($userId, $resourceName);

                if (empty($OAuthEz)) {
                    continue;
                }

                $externalId = $OAuthEz->getResourceUserId();
                $profileUrls[$resourceName] = $resourceBaseUrl.$externalId;
            }
        }

        return $profileUrls;
    }
}
