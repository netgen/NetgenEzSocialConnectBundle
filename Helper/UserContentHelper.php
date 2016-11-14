<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\API\Repository\Values\User\UserCreateStruct;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use Netgen\Bundle\EzSocialConnectBundle\Exception\MissingConfigurationException;
use Netgen\Bundle\EzSocialConnectBundle\Exception\ResourceOwnerNotSupportedException;
use Netgen\Bundle\EzSocialConnectBundle\Exception\UserNotConnectedException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use Symfony\Component\Filesystem\Exception\IOException;
use Psr\Log\LoggerInterface;
use eZ\Publish\Core\Helper\FieldHelper;
use Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

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
     * @param \eZ\Publish\API\Repository\Repository                                     $repository
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface                              $configResolver
     * @param \eZ\Publish\Core\Helper\FieldHelper                                       $fieldHelper
     * @param \Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository  $OAuthEzRepository
     * @param \Psr\Log\LoggerInterface                                                  $logger
     */
    public function __construct(
        Repository $repository,
        ConfigResolverInterface $configResolver,
        FieldHelper $fieldHelper,
        OAuthEzRepository $OAuthEzRepository,
        LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->fieldHelper = $fieldHelper;
        $this->OAuthEzRepository = $OAuthEzRepository;
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

        if (!$data) {
            throw new IOException('Could not download image.');
        }

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

        $imageFieldExists = $this->contentFieldExists($user->content, $imageFieldIdentifier);

        if (!$imageFieldExists
            || empty($imageFieldIdentifier)
            || !$this->fieldHelper->isFieldEmpty($user->content, $imageFieldIdentifier, $language)
        ) {
            return false;
        }

        try {
            $imageFileName = $this->downloadExternalImage($imageLink);
        } catch (IOException $e) {
            $this->logger->error("Problem while saving image {$imageLink}: ".$e->getMessage());

            return false;
        }

        $this->logger->notice("Local image created: {$imageFileName}.");

        if (!$language) {
            $language = $this->getFirstConfiguredLanguage();
        }

        $contentService = $this->getRepository()->getContentService();

        $userDraft = $contentService->createContentDraft($user->content->versionInfo->contentInfo);
        $userUpdateStruct = $contentService->newContentUpdateStruct();
        $userUpdateStruct->initialLanguageCode = $language;
        $userUpdateStruct->setField($imageFieldIdentifier, $imageFileName);

        $this->getRepository()->sudo(
            function (Repository $repository) use ($userDraft, $userUpdateStruct) {
                $contentService = $repository->getContentService();
                $userDraft = $contentService->updateContent($userDraft->versionInfo, $userUpdateStruct);
                $contentService->publishVersion($userDraft->versionInfo);
            }
        );

        return true;
    }

    /**
     * Before checking if a field is empty, we are interested in whether it exists at all.
     *
     * @param Content $content
     * @param $fieldIdentifier
     *
     * @return bool
     */
    protected function contentFieldExists(Content $content, $fieldIdentifier)
    {
        return array_reduce($content->getFields(), function($accumulator, Field $field) use ($fieldIdentifier) {
            if ($field->fieldDefIdentifier === $fieldIdentifier) $accumulator = true;

            return $accumulator;
        }, $initial = false);
    }

    /**
     * Creates ez user from OAuthEzUser entity.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser    $oauthUser
     * @param string                                                    $language
     *
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function createEzUser(OAuthEzUser $oauthUser, $language = null)
    {
        $contentTypeIdentifier = $this->configResolver->getParameter('user_content_type_identifier', 'netgen_social_connect');
        $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);

        if (!$language) {
            $language = $this->getFirstConfiguredLanguage();
        }

        $userCreateStruct = $this->getUserCreateStruct($oauthUser, $contentType, $language);

        $userGroupId = $this->getUserGroupId($oauthUser);

        $newUser = $this->getRepository()->sudo(
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
     *
     * @return void
     */
    public function updateUserFields(User $user, array $fields)
    {
        try {
            $userUpdateStruct = $this->getRepository()->getUserService()->newUserUpdateStruct();

            foreach ($fields as $name => $value) {
                if ($this->contentFieldExists($user, $name)) {
                    $userUpdateStruct->$name = $value;
                } else if ($this->logger instanceof LoggerInterface) {

                    $this->logger->error("SocialConnect - User class has no field '{$name}'");
                }
            }

            $this->getRepository()->sudo(
                function (Repository $repository) use ($user, $userUpdateStruct) {
                    return $repository->getUserService()->updateUser($user, $userUpdateStruct);
                }
            );

        } catch (\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e) {
            $message = 'User not allowed to update the user object.';

        } catch (\eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException $e) {
            $message = 'The field input is not valid.';

        } catch (\eZ\Publish\API\Repository\Exceptions\ContentValidationException $e) {
            $message = 'Required field is empty.';

        } catch (\eZ\Publish\API\Repository\Exceptions\InvalidArgumentException $e) {
            $message = 'Field type does not accept this value.';

        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        if (isset($e) && $this->logger instanceof LoggerInterface) {
            $fieldNames = array_keys($fields);
            $fieldNamesString = implode(', ', $fieldNames);

            $this->logger->error("User Id {$user->id}, field {$fieldNamesString} failed to update: ".PHP_EOL.$message);
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
        return $this->getRepository()->getUserService()->loadUser($userId);
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

            $OAuthEz = $this->OAuthEzRepository->loadFromTableByEzId($userId, $resourceName);

            if (empty($OAuthEz)) {
                throw new UserNotConnectedException($resourceName);
            }

            $externalId = $OAuthEz->getResourceUserId();

            $profileUrls[$resourceName] = $this->baseUrls[$resourceName].$externalId;

        } else {
            foreach ($this->baseUrls as $resourceName => $resourceBaseUrl) {
                $OAuthEz = $this->OAuthEzRepository->loadFromTableByEzId($userId, $resourceName);

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
            } catch (IOException $e) {
                $this->logger->error("SocialConnect: Problem while saving image {$imageLink}: " . $e->getMessage());
            }
        }

        return $userCreateStruct;
    }

    /**
     * Fetches the UserGroupId from the YAML configuration.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $oauthEzUser
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
    public function getUserCreateStruct(OAuthEzUser $oauthEzUser, ContentType $contentType, $language)
    {
        $username = $oauthEzUser->getUsername();
        $password = $this->createPassword($oauthEzUser->getOriginalId(), $username);
        $firstName = $oauthEzUser->getFirstName();
        $lastName = $oauthEzUser->getLastName();
        $imageLink = $oauthEzUser->getImagelink();

        $userCreateStruct = $this->getRepository()->getUserService()->newUserCreateStruct(
            $username, $oauthEzUser->getEmail(), $password, $language, $contentType
        );

        $userCreateStruct = $this->addFieldIfExists($userCreateStruct, $contentType, $this->firstNameIdentifier, $firstName);
        $userCreateStruct = $this->addFieldIfExists($userCreateStruct, $contentType, $this->lastNameIdentifier, $lastName);

        if ($this->isImageFieldDefined()
            && $contentType->getFieldDefinition($this->imageFieldIdentifier) instanceof FieldDefinition) {
            $userCreateStruct = $this->getImageIfExists($userCreateStruct, $imageLink);
        }
        $userCreateStruct->enabled = true;

        return $userCreateStruct;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\User\UserCreateStruct $userCreateStruct
     * @param string $fieldDefinitionIdentifier
     * @param mixed $value
     *
     * @return \eZ\Publish\API\Repository\Values\User\UserCreateStruct
     */
    protected function addFieldIfExists(UserCreateStruct $userCreateStruct, ContentType $contentType, $fieldDefinitionIdentifier, $value)
    {
        $fieldDefinition = $contentType->getFieldDefinition($fieldDefinitionIdentifier);

        if ($fieldDefinition instanceof FieldDefinition) {
            if (!empty($value)) {
                $userCreateStruct->setField($this->lastNameIdentifier, $lastName);
            }

            return $userCreateStruct;
        }

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->error("SocialConnect: Could not map  {$fieldDefinitionIdentifier} to user content. Field does not exist.");

            throw new AuthenticationException();
        }
    }

    /**
     *
     * @param string $originalId
     * @param string $username
     *
     * @return bool|false|string
     */
    public function createPassword($originalId, $username)
    {
        return password_hash(str_shuffle($originalId.microtime().$username), PASSWORD_DEFAULT);
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getFirstConfiguredLanguage()
    {
        $languages = $this->configResolver->getParameter('languages');

        return reset($languages);
    }

    /**
     * @codeCoverageIgnore
     * @return \eZ\Publish\API\Repository\Repository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * @codeCoverageIgnore
     * @return bool
     */
    public function isImageFieldDefined()
    {
        return !empty($this->imageFieldIdentifier);
    }
}
