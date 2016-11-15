<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Security\User as SecurityUser;
use Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository;
use Netgen\Bundle\EzSocialConnectBundle\Helper\UserContentHelper;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use eZ\Publish\Core\Repository\Values\User\User;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use eZ\Publish\Core\MVC\Symfony\Security\User\Provider as BaseUserProvider;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;

class eZUserProvider extends BaseUserProvider implements OAuthAwareUserProviderInterface
{
    /**
     * @var \Netgen\Bundle\EzSocialConnectBundle\Helper\UserContentHelper
     */
    protected $userContentHelper;

    /** @var  \Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository */
    protected $OAuthEzRepository;

    /**
     * @var bool
     */
    protected $mergeAccounts;

    /**
     * eZUserProvider constructor.
     *
     * @codeCoverageIgnore
     * @param \eZ\Publish\API\Repository\Repository                                     $repository
     * @param \Netgen\Bundle\EzSocialConnectBundle\Helper\UserContentHelper             $userContentHelper
     * @param \Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository  $OAuthEzRepository
     */
    public function __construct(Repository $repository, UserContentHelper $userContentHelper, OAuthEzRepository $OAuthEzRepository)
    {
        parent::__construct($repository);

        $this->userContentHelper = $userContentHelper;
        $this->OAuthEzRepository = $OAuthEzRepository;
    }

    /**
     * Injected setter
     *
     * @codeCoverageIgnore
     * @param bool $mergeAccounts
     */
    public function setMergeAccounts($mergeAccounts = false)
    {
        $this->mergeAccounts = $mergeAccounts;
    }

    /**
     * Loads the user by a given UserResponseInterface object.
     * If no eZ user is found with those credentials, a real eZ User content object is generated.
     *
     * @param \HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface $response

     * @return \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     *
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $OAuthEzUserEntity = $this->OAuthEzRepository->loadFromTableByResourceUserId(
            $response->getUsername(), $response->getResourceOwner()->getName()
        );

        // Intermediary user entity generated from the response, not stored in the database
        $OAuthEzUser = $this->generateOAuthEzUser($response);

        if ($OAuthEzUserEntity instanceof OAuthEz) {

            $linkedUser = $this->getLinkedUser($OAuthEzUserEntity, $OAuthEzUser);

            if ($linkedUser instanceof SecurityUserInterface) {
                return $linkedUser;
            }
        }

        if (!$this->getMergeAccounts()) {
            $userContentObject = $this->userContentHelper->createEzUser($OAuthEzUser);
            $this->OAuthEzRepository->addToTable($userContentObject, $OAuthEzUser, false);

            return $this->loadUserByAPIUser($userContentObject);
        }

        $securityUser = $this->getFirstUserByEmail($OAuthEzUser->getEmail());

        if ($securityUser instanceof SecurityUserInterface) {
            $this->OAuthEzRepository->addToTable($securityUser->getAPIUser(), $OAuthEzUser, true);

            return $securityUser;
        }

        try {
            $securityUser = $this->loadUserByUsername($OAuthEzUser->getUsername());
            $this->OAuthEzRepository->addToTable($securityUser->getAPIUser(), $OAuthEzUser, true);

            return $securityUser;

        } catch (\Symfony\Component\Security\Core\Exception\UsernameNotFoundException $e) {
            $userContentObject = $this->userContentHelper->createEzUser($OAuthEzUser);
            $this->OAuthEzRepository->addToTable($userContentObject, $OAuthEzUser, false);

            return $this->loadUserByAPIUser($userContentObject);
        }
    }

    /**
     * Generates an OAuthEzUser object from the OAuth response.
     *
     * This is an intermediary object used to generate Ez Users if none exist with those OAuth credentials.
     *
     * @param \HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface $response
     *
     * @return \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    protected function generateOAuthEzUser(UserResponseInterface $response)
    {
        $userId = $response->getUsername();
        $uniqueLogin = $response->getNickname().'-'.$userId;

        $OAuthEzUser = new OAuthEzUser($uniqueLogin, $userId);

        $username = $this->getRealName($response);
        $OAuthEzUser->setFirstName($username['firstName']);
        $OAuthEzUser->setLastName($username['lastName']);
        $OAuthEzUser->setEmail($this->getEmail($response, $userId));
        $OAuthEzUser->setResourceOwnerName($response->getResourceOwner()->getName());

        if ($response->getProfilePicture()) {
            $OAuthEzUser->setImageLink($response->getProfilePicture());
        }

        return $OAuthEzUser;
    }

    /**
     * Fetches the email from the response or generates a dummy email hash if the resource provider refused to,
     * or could not share the email.
     *
     * @param \HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface $response
     * @param $userId
     *
     * @return string
     */
    protected function getEmail(UserResponseInterface $response, $userId)
    {
        $responseEmail = $response->getEmail();

        if (!empty($responseEmail)) {
            $email = $responseEmail;
        } else {
            $email = md5('socialbundle'.$response->getResourceOwner()->getName().$userId).'@localhost.local';
        }

        return $email;
    }

    /**
     * If a link was found, update profile data for the user.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz       $OAuthEzUserEntity
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser    $OAuthEzUser
     *
     * @return \eZ\Publish\Core\MVC\Symfony\Security\User|null
     */
    protected function getLinkedUser(OAuthEz $OAuthEzUserEntity, OAuthEzUser $OAuthEzUser)
    {
        try {
            $userContentObject = $this->userContentHelper->loadEzUserById($OAuthEzUserEntity->getEzUserId());

            // If the parameter 'profile_image' is not defined, skip this step.
            if ($this->userContentHelper->isImageFieldDefined()) {
                $imageLink = $OAuthEzUser->getImageLink();
                if (!empty($imageLink)) {
                    $this->userContentHelper->addProfileImage($userContentObject, $imageLink);
                }
            }

            // Check whether an email was returned by the OAuth provider. If not, a dummy 'localhost.local' will be found.
            // Dummy emails usually the result of missing resource owner app permissions for email sharing.
            if ($OAuthEzUser->getEmail() !== $userContentObject->email &&
                0 !== strpos(strrev($OAuthEzUser->getEmail()), 'lacol.tsohlacol')) {
                $this->userContentHelper->updateUserFields($userContentObject, array('email' => $OAuthEzUser->getEmail()));
            }

            return $this->loadUserByAPIUser($userContentObject);

        } catch (NotFoundException $e) {
            // Something went wrong - data is in the table, but the user does not exist
            // Remove faulty data and fall back to creating a new user
            $this->OAuthEzRepository->removeFromTable($OAuthEzUserEntity);

            return null;
        }
    }

    /**
     * Generates a first and last name from the response.
     *
     * @param \HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface $response
     *
     * @return array
     */
    protected function getRealName(UserResponseInterface $response)
    {
        $realName = $response->getRealName();

        if (!empty($realName)) {
            $realName = explode(' ', $realName);

            if (count($realName) >= 2) {
                $firstName = array_shift($realName);
                $lastName = implode(' ', $realName);
            } else {
                // @codeCoverageIgnoreStart
                $firstName = reset($realName);
                // @codeCoverageIgnoreEnd
                $lastName = reset($realName);
            }
        } else {
            $userEmail = $response->getEmail();

            if (!empty($userEmail)) {
                $emailArray = explode('@', $userEmail);

                $firstName = reset($emailArray);
                $lastName = reset($emailArray);
            } else {
                $firstName = $response->getNickname();
                $lastName = $response->getResourceOwner()->getName();
            }
        }

        return array('firstName' => $firstName, 'lastName' => $lastName);
    }

    /**
     * Converts a value object User to a Security user.
     *
     * @param string $email
     *
     * @codeCoverageIgnore due to loadUsersByEmail being handled by the eZ user service
     *
     * @return \eZ\Publish\Core\MVC\Symfony\Security\User|null
     */
    protected function getFirstUserByEmail($email)
    {
        $users = $this->repository->getUserService()->loadUsersByEmail($email);
        $user = reset($users);

        if ($user instanceof User) {
            return new SecurityUser($user, array('ROLE_USER'));
        }

        return null;
    }

    /**
     * Returns the value of the mergeAccounts flag.
     *
     * @codeCoverageIgnore
     * @return bool
     */
    protected function getMergeAccounts()
    {
        return $this->mergeAccounts;
    }
}
