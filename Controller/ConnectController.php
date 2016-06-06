<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse;
use Netgen\Bundle\EzSocialConnectBundle\Exception\UserAlreadyConnectedException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use eZ\Publish\Core\MVC\Symfony\Security\UserInterface;
use Symfony\Component\Translation\Translator;

class ConnectController extends Controller
{
    /**
     * Removes the link between the currently logged-in user and the resource owner.
     * On success, it adds a flashbag notice and redirects to referer.
     *
     * Disconnecting from the primary social account is not allowed - if a user account was created
     * specifically from a social connect event, that account is tied to its resource provider.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $resourceName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException if the user is not logged in.
     * @throws \eZ\Publish\Core\Base\Exceptions\NotFoundException if the social link is flagged as non-removable.
     */
    public function disconnectUser(Request $request, $resourceName)
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedHttpException("Cannot disconnect from '{$resourceName}'. Please log in first.");
        }

        $userContentId = $user->getAPIUser()->id;
        $loginHelper = $this->get('netgen.social_connect.helper');
        $OAuthEz = $loginHelper->loadFromTableByEzId($userContentId, $resourceName, true);

        if (empty($OAuthEz)) {
            throw new NotFoundException('Disconnectable user', $userContentId.'/'.$resourceName);
        }

        $loginHelper->removeFromTable($OAuthEz);
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $request->getSession();

        $translator = $this->get('translator');
        $message = $translator->trans(
            'disconnect.owner.success', array('ownerName' => ucfirst($resourceName)), 'social_connect'
        );
        $session->getFlashBag()->add('notice', $message);

        return $this->redirect($request->headers->get('referer', '/'));
    }

    /**
     * Sets the ez user id into session variable, and starts the connection to the social network.
     * This will redirect to the finishConnecting route which will have its request populated with OAuth data.
     *
     * Stores the initial referer so the finishConnecting route can return to it when done.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $resourceName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse

     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException if the user is not logged in.
     * @throws \Netgen\Bundle\EzSocialConnectBundle\Exception\UserAlreadyConnectedException if the user already has a link to that resource owner.
     */
    public function connectUser(Request $request, $resourceName)
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface)
        {
            throw new AccessDeniedHttpException("Cannot connect to '{$resource_name}'. Please log in first.");
        }

        $userContentId = $user->getAPIUser()->id;

        $loginHelper = $this->get( 'netgen.social_connect.helper' );

        $OAuthEz = $loginHelper->loadFromTableByEzId( $userContentId, $resource_name );

        if ( !empty( $OAuthEz ) )
        {
            throw new UserAlreadyConnected( $resource_name );
        }

        $session = $request->getSession();
        $session->set('social_connect_ez_user_id', $userContentId);
        $session->set('social_connect_resource_owner', $resourceName);

        // Handle targetPath in session to prevent issues with GET parameters in Facebook redirect uri.
        $session->set('social_connect_target_path', $request->headers->get('referer', '/'));

        /** @var \HWI\Bundle\OAuthBundle\Security\OAuthUtils $OAuthUtils */
        $OAuthUtils = $this->container->get('hwi_oauth.security.oauth_utils');

        return $this->redirect($OAuthUtils->getAuthorizationUrl($request, $resourceName, $this->getRedirectUrl()));
    }

    /**
     * Get a resource owner object by name.
     *
     * @param string $name
     *
     * @return \HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface
     *
     * @throws \RuntimeException if there is no resource owner with the given name.
     */
    protected function getResourceOwnerByName($name)
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface)
        {
            throw new AccessDeniedHttpException("Cannot disconnect from '{$resource_name}'. Please log in first.");
        }

        $userContentId = $user->getAPIUser()->id;

        $loginHelper = $this->get('netgen.social_connect.helper');
        $OAuthEz = $loginHelper->loadFromTableByEzId($userContentId, $resource_name);

        if (empty($OAuthEz))
        {
            throw new NotFoundException('connected user', $userContentId.'/'.$resource_name);
        }

        return $resourceOwner;
    }

    /**
     * Given a request with an authorization code, fetches a token, retrieves a user resource id.
     * Then, a link is saved between the logged-in user and the resource owner.
     *
     * On failure or success, redirect to the target path with a flash notice.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function finishConnecting(Request $request)
    {
        /** @var Translator $translator */
        $translator = $this->get('translator');
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $request->getSession();
        $flashBag = $session->getFlashBag();

        if (!$session->has('social_connect_target_path')) {
            $flashBag->add('notice', $translator->trans('connect.generic.failed', array(), 'social_connect'));

            return $this->redirect('/');
        }

        $targetPath = $session->get('social_connect_target_path');
        $session->remove('social_connect_target_path');

        $resourceOwnerName = $session->get('social_connect_resource_owner');
        $session->remove('social_connect_resource_owner');

        $message = $translator->trans('connect.owner.failed', array('ownerName' => ucfirst($resourceOwnerName)), 'social_connect');

        try {
            $resourceOwner = $this->getResourceOwnerByName($resourceOwnerName);
        } catch (\RuntimeException $e) {
            $flashBag->add('notice', $message);

            return $this->redirect($targetPath);
        }


        $apiUser = $this->getUser()->getAPIUser();

        if (!($session->has('social_connect_ez_user_id') && $apiUser->id == $session->get('social_connect_ez_user_id'))) {

            $flashBag->add('notice', $message);

            return $this->redirect($targetPath);
        }

        $session->remove('social_connect_ez_user_id');

        $token = $resourceOwner->getAccessToken($request, $this->getRedirectUrl());
        $userInformation = $resourceOwner->getUserInformation($token);

        if (!$userInformation instanceof PathUserResponse) {
            $message = $translator->trans(
                'connect.owner.already_connected', array('ownerName' => ucfirst($resourceOwnerName)), 'social_connect'
            );

            $flashBag->add('notice', $message);

            return $this->redirect($targetPath);
        }

        $resourceUserId = $userInformation->getUsername();

        $loginHelper = $this->get('netgen.social_connect.helper');

        if (!empty($loginHelper->loadFromTableByResourceUserId($resourceUserId, $resourceOwnerName))) {
            $flashBag->add('notice', $message);

            return $this->redirect($targetPath);
        }

        $loginHelper->addToTable(
            $loginHelper->loadEzUserById($apiUser->id),
            $this->getOAuthEzUser($apiUser->login, $resourceOwner->getName(), $resourceUserId),
            true
        );

        $flashBag->add('notice', $message);

        return $this->redirect($targetPath);
    }

    /**
     * Wrapper for intermediary OAuthEzUser object for addToTable call.
     *
     * @param string $username
     * @param string $resourceOwnerName
     * @param string $resourceUserId
     *
     * @return \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    protected function getOAuthEzUser($username, $resourceOwnerName, $resourceUserId)
    {
        $OAuthEz = new OAuthEzUser($username, $resourceUserId);
        $OAuthEz->setResourceOwnerName($resourceOwnerName);

        return $OAuthEz;
    }

    /**
     * Fetches the redirect URL for the OAuth response consumer.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        return $this->generateUrl('netgen_finish_connecting', array(), UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
