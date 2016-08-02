<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzSocialConnectBundle\Exception\MissingConfigurationException;
use Netgen\Bundle\EzSocialConnectBundle\Exception\ResourceOwnerNotSupportedException;
use Netgen\Bundle\EzSocialConnectBundle\Exception\UserNotConnectedException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use Symfony\Component\Filesystem\Exception\IOException;
use Psr\Log\LoggerInterface;
use eZ\Publish\Core\Helper\FieldHelper;

class UserContentHelper
{
    /** @var \eZ\Publish\API\Repository\Repository Repository */
    protected $repository;

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
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \eZ\Publish\Core\Helper\FieldHelper          $fieldHelper
     * @param \Psr\Log\LoggerInterface                     $logger
     */
    public function __construct(
        Repository $repository,
        ConfigResolverInterface $configResolver,
        FieldHelper $fieldHelper,
        LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->fieldHelper = $fieldHelper;
        $this->logger = $logger;
    }

    /**
     * Injected setter
     *
     * @codeCoverageIgnore
     * @param $firstNameIdentifier
     */
    public function setFirstNameIdentifier($firstNameIdentifier = null){
        $this->firstNameIdentifier = $firstNameIdentifier;
    }

    /**
     * Injected setter
     *
     * @codeCoverageIgnore
     * @param $lastNameIdentifier
     */
    public function setLastNameIdentifier($lastNameIdentifier = null){
        $this->lastNameIdentifier = $lastNameIdentifier;
    }

    /**
     * Injected setter
     *
     * @codeCoverageIgnore
     * @param $imageFieldIdentifier
     */
    public function setProfileImageIdentifier($imageFieldIdentifier = null){
        $this->imageFieldIdentifier = $imageFieldIdentifier;
    }

    /**
     * Injected setter
     *
     * @codeCoverageIgnore
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
        $imageFieldIdentifier = $this->imageFieldIdentifier;
        if (empty($imageFieldIdentifier)
            || !$this->fieldHelper->isFieldEmpty($user->content, $imageFieldIdentifier, $language)
        ) {
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
            $language = $this->getFirstConfiguredLanguage();
        }

        $contentService = $this->repository->getContentService();

        $userDraft = $contentService->createContentDraft($user->content->versionInfo->contentInfo);
        $userUpdateStruct = $contentService->newContentUpdateStruct();
        $userUpdateStruct->initialLanguageCode = $language;
        $userUpdateStruct->setField($imageFieldIdentifier, $imageFileName);

        $this->repository->sudo(
            function (Repository $repository) use ($userDraft, $userUpdateStruct) {
                $contentService = $repository->getContentService();
                $userDraft = $contentService->updateContent($userDraft->versionInfo, $userUpdateStruct);
                $contentService->publishVersion($userDraft->versionInfo);
            }
        );

        return true;
    }


    /**
     * Creates ez user from OAuthEzUser entity.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $oauthUser
     *
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function createEzUser(OAuthEzUser $oauthUser, $language = null)
    {
        $contentTypeIdentifier = $this->configResolver->getParameter('user_content_type_identifier', 'netgen_social_connect');
        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);

        if (!$language) {
            $language = $this->getFirstConfiguredLanguage();
        }

        $userCreateStruct = $this->getUserCreateStruct($oauthUser, $contentType, $language);

        $userGroupId = $this->getUserGroupId($oauthUser);

        $newUser = $this->repository->sudo(
            function (Repository $repository) use ($userCreateStruct, $userGroupId) {
                $userService = $repository->getUserService();
                $userGroup = $userService->loadUserGroup($userGroupId);

                return $userService->createUser($userCreateStruct, array($userGroup));
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

    /**
     * Attempts to fetch the remote image and populate the imageField in the UserCreateStruct.
     *
     * @param \eZ\Publish\API\Repository\Values\User\UserCreateStruct   $userCreateStruct
     * @param string                                                    $imageLink
     *
     * @return \eZ\Publish\API\Repository\Values\User\UserCreateStruct
     */
    public function getImageIfExists($userCreateStruct, $imageLink)
    {
        if (!empty($imageLink) && !empty($this->imageFieldIdentifier)) {
            $imageFileName = null;
            try {
                $imageFileName = $this->downloadExternalImage($imageLink);
                $userCreateStruct->setField($this->imageFieldIdentifier, $imageFileName);
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                $this->logger->error("Problem while saving image {$imageLink}: " . $e->getMessage());
            }
        }

        return $userCreateStruct;
    }

    /**
     * Fetches the UserGroupId from the YAML configuration.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $oauthUser
     *
     * @return string
     *
     * @throws \Netgen\Bundle\EzSocialConnectBundle\Exception\MissingConfigurationException if the user group is not set up.
     */
    public function getUserGroupId(OAuthEzUser $oauthEzUser)
    {
        $userGroupParameter = $oauthEzUser->getResourceOwnerName() . '.user_group';

        if (!$this->configResolver->hasParameter($userGroupParameter, 'netgen_social_connect')) {
            throw new MissingConfigurationException($userGroupParameter);
        }

        $userGroupId = $this->configResolver->getParameter($userGroupParameter, 'netgen_social_connect');

        return $userGroupId;
    }

    /**
     * Creates and populates the userCreateStruct from the OAuthEzUser.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser    $oauthEzUser
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     * @param string                                                    $language
     *
     * @return \eZ\Publish\API\Repository\Values\User\UserCreateStruct
     */
    public function getUserCreateStruct(OAuthEzUser $oauthEzUser, $contentType, $language)
    {
        $username = $oauthEzUser->getUsername();
        $password = password_hash(str_shuffle($oauthEzUser->getOriginalId().microtime().$username), PASSWORD_DEFAULT);
        $firstName = $oauthEzUser->getFirstName();
        $lastName = $oauthEzUser->getLastName();
        $imageLink = $oauthEzUser->getImagelink();

        $userCreateStruct = $this->repository->getUserService()->newUserCreateStruct(
            $username, $oauthEzUser->getEmail(), $password, $language, $contentType
        );

        if (!empty($firstName) && !empty($this->firstNameIdentifier)) {
            $userCreateStruct->setField($this->firstNameIdentifier, $firstName);
        }

        if (!empty($lastName) && !empty($this->lastNameIdentifier)) {
            $userCreateStruct->setField($this->lastNameIdentifier, $lastName);
        }

        $userCreateStruct = $this->getImageIfExists($userCreateStruct, $imageLink);
        $userCreateStruct->enabled = true;

        return $userCreateStruct;
    }

    /**
     * @return string
     */
    public function getFirstConfiguredLanguage()
    {
        $languages = $this->configResolver->getParameter('languages');

        return reset($languages);
    }
}
