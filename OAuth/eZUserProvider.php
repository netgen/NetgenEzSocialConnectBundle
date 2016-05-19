<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Security\User as SecurityUser;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use eZ\Publish\Core\Repository\Values\User\User;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use eZ\Publish\Core\MVC\Symfony\Security\User\Provider as BaseUserProvider;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;

class eZUserProvider extends BaseUserProvider implements OAuthAwareUserProviderInterface
{
    /**
     * @var \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper
     */
    protected $loginHelper;

    /**
     * @var bool
     */
    protected $mergeAccounts;

    /**
     * eZUserProvider constructor.
     *
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper $loginHelper
     */
    public function __construct(Repository $repository, SocialLoginHelper $loginHelper)
    {
        parent::__construct($repository);

        $this->loginHelper = $loginHelper;
    }

    /**
     * Injected setter
     *
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
        $OAuthEzUserEntity = $this->loginHelper->loadFromTableByResourceUserId(
            $response->getUsername(), $response->getResourceOwner()->getName()
        );

        // Intermediary user entity generated from the response, not stored in the database
        $OAuthEzUser = $this->generateOAuthEzUser($response);

        // If a link was found, update profile data for the user
        if ($OAuthEzUserEntity instanceof OAuthEz) {
            try {
                $userContentObject = $this->loginHelper->loadEzUserById($OAuthEzUserEntity->getEzUserId());

                $imageLink = $OAuthEzUser->getImageLink();
                if (!empty($imageLink)) {
                    $this->loginHelper->addProfileImage($userContentObject, $imageLink);
                }

                // If the email is 'localhost.local', we did not fetch it remotely from the OAuth resource provider
                if (
                    $OAuthEzUser->getEmail() !== $userContentObject->email &&
                    0 !== strpos(strrev($OAuthEzUser->getEmail()), 'lacol.tsohlacol')
                )
                {
                    $this->loginHelper->updateUserFields($userContentObject, array('email' => $OAuthEzUser->getEmail()));
                }

                return $this->loadUserByAPIUser($userContentObject);

            } catch (NotFoundException $e) {
                // Something went wrong - data is in the table, but the user does not exist
                // Remove faulty data and fall back to creating a new user
                $this->loginHelper->removeFromTable($OAuthEzUserEntity);
            }
        }
        // If there is no link, look for an eZ user and connect them
        if ($this->mergeAccountsFlag) {
            $securityUser = $this->getFirstUserByEmail($OAuthEzUser->getEmail());

            if (!$securityUser instanceof SecurityUserInterface) {
                $securityUser = $this->loadUserByUsername($OAuthEzUser->getUsername());
            }
            $this->loginHelper->addToTable($securityUser->getAPIUser(), $OAuthEzUser, OAuthEz::NOT_DISCONNECTABLE);

            return $securityUser;
        }

        $userContentObject = $this->loginHelper->createEzUser($OAuthEzUser);
        $this->loginHelper->addToTable($userContentObject, $OAuthEzUser, OAuthEz::NOT_DISCONNECTABLE);

        return $this->loadUserByAPIUser($userContentObject);
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

        if (null === $response->getEmail()) {
            $email = md5('socialbundle'.$response->getResourceOwner()->getName().$userId).'@localhost.local';
        } else {
            $email = $response->getEmail();
        }
        $OAuthEzUser->setEmail($email);

        $OAuthEzUser->setResourceOwnerName($response->getResourceOwner()->getName());

        if ($response->getProfilePicture()) {
            $OAuthEzUser->setImageLink($response->getProfilePicture());
        }

        return $OAuthEzUser;
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
                $firstName = reset($realName);
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
}
