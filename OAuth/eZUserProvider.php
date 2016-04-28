<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class eZUserProvider implements OAuthAwareUserProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var SocialLoginHelper
     */
    protected $loginHelper;

    /**
     * eZUserProvider constructor.
     *
     * @param SocialLoginHelper     $loginHelper
     * @param UserProviderInterface $userProvider
     */
    public function __construct(SocialLoginHelper $loginHelper, UserProviderInterface $userProvider)
    {
        $this->loginHelper = $loginHelper;
        $this->userProvider = $userProvider;
    }

    /**
     * Loads the user by a given UserResponseInterface object.
     *
     * If no eZ user is found those credentials, a real eZ User content object is generated.
     *
     *
     * @param UserResponseInterface $response
     *
     * @return OAuthEzUser
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        /** @var OAuthEzUser $user */
        $OAuthEzUser = $this->getOAuthEzUser($response);

        $OAuthEzUserEntity = $this->loginHelper->loadFromTable($OAuthEzUser);

        if (!empty($OAuthEzUserEntity)) {
            try {
                // If the user account is not linked to the external table, add the link and fill in available fields.

                $ezUserId = $OAuthEzUserEntity->getEzUserId();
                $userContentObject = $this->loginHelper->loadEzUserById($ezUserId);

                $imageLink = $OAuthEzUser->getImageLink();
                if (!empty($imageLink)) {
                    $this->loginHelper->addProfileImage($userContentObject, $imageLink);
                }

                if ($OAuthEzUser->getEmail() !== $userContentObject->email && !strpos(strrev($OAuthEzUser->getEmail()), 'lacol.tsohlacol') === 0) {
                    $this->loginHelper->updateUserFields($userContentObject, array("email" => $OAuthEzUser->getEmail()));
                }

                return $this->userProvider->loadUserByUsername($userContentObject->login);

            } catch (\eZ\Publish\API\Repository\Exceptions\NotFoundException $e) {

                 // Something went wrong - data is in the table, but the user does not exist.
                 // Remove faulty data and fall back to creating a new user.

                $this->loginHelper->removeFromTable($OAuthEzUserEntity);
            }
        } else {

            // Otherwise, try to load the existing, linked user
            try {
                $user = $this->userProvider->loadUserByUsername($OAuthEzUser->getUsername());

            } catch (UsernameNotFoundException $e) {
                $userContentObject = $this->loginHelper->createEzUser($OAuthEzUser);
                $this->loginHelper->addToTable($userContentObject, $OAuthEzUser);
            }
        }

        return $user;
    }

    /**
     * Generates an eZ User object from the OAuth response,
     *
     * @param \HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface $response
     *
     * @return \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    protected function getOAuthEzUser(UserResponseInterface $response)
    {
        $userId = $response->getUsername();
        $uniqueLogin = $response->getNickname().'-'.$userId;

        $OAuthEzUser = new OAuthEzUser($uniqueLogin, $userId);

        $realName = $response->getRealName();

        if (!empty($realName)) {
            $realName = explode(' ', $realName);

            if (count($realName) >= 2) {
                $OAuthEzUser->setFirstName(array_shift($realName));
                $OAuthEzUser->setLastName(implode(' ', $realName));
            } else {
                $OAuthEzUser->setFirstName(reset($realName));
                $OAuthEzUser->setLastName(reset($realName));
            }
        } else {
            $userEmail = $response->getEmail();
            if (!empty($userEmail)) {
                $emailArray = explode('@', $userEmail);
                $OAuthEzUser->setFirstName(reset($emailArray));
                $OAuthEzUser->setLastName(reset($emailArray));
            } else {
                $OAuthEzUser->setFirstName($response->getNickname());
                $OAuthEzUser->setLastName($response->getResourceOwner()->getName());
            }
        }

        if (null === $response->getEmail()) {
            $email = md5('socialbundle'.$response->getResourceOwner()->getName().$userId).'@localhost.local';
            $OAuthEzUser->setEmail($email);
        } else {
            $OAuthEzUser->setEmail($response->getEmail());
        }

        $OAuthEzUser->setResourceOwnerName($response->getResourceOwner()->getName());

        if ($response->getProfilePicture()) {
            $OAuthEzUser->setImageLink($response->getProfilePicture());
        }

        return $OAuthEzUser;
    }
}
