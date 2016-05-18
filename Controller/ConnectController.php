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

class ConnectController extends Controller
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $resourceName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \eZ\Publish\Core\Base\Exceptions\NotFoundException
     */
    public function disconnectUser(Request $request, $resourceName)
    {
        if (!$this->getUser() instanceof UserInterface) {
            throw new AccessDeniedHttpException(sprintf("Cannot disconnect from '%s'. Please log in first.", $resourceName));
        }

        $targetPathParameter = $this->container->getParameter('hwi_oauth.target_path_parameter');
        $targetPath = $request->query->get($targetPathParameter, '/');
        $userContentId = $this->getUser()->getAPIUser()->id;
        $loginHelper = $this->get('netgen.social_connect.helper');
        $OAuthEz = $loginHelper->loadFromTableByEzId($userContentId, $resourceName);

        if (empty($OAuthEz)) {
            throw new NotFoundException('connected user', $userContentId.'/'.$resourceName);
        }
        if (!$OAuthEz->isIsDisconnectable()) {
            throw new \InvalidArgumentException(sprintf("Cannot disconnect from '%s' as it is the main social login.", $resourceName));
        }

        $loginHelper->removeFromTable($OAuthEz);
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('notice', 'You have successfully disconnected user from Facebook account!');

        return $this->redirect(
            $targetPath
        );
    }

    /**
     * Sets the ez user id into session variable,
     * and starts the connection to the social network.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $resourceName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Netgen\Bundle\EzSocialConnectBundle\Exception\UserAlreadyConnectedException
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

        $request->getSession()->set( 'social_connect_ez_user_id', $userContentId);
        $request->getSession()->set( 'social_connect_resource_owner', $resourceName);

        /** @var \HWI\Bundle\OAuthBundle\Security\OAuthUtils $OAuthUtils */
        $OAuthUtils = $this->container->get('hwi_oauth.security.oauth_utils');

        return $this->redirect(
            $OAuthUtils->getAuthorizationUrl(
                $request,
                $resourceName,
                $this->generateUrl('netgen_finish_connecting', array(), UrlGeneratorInterface::ABSOLUTE_URL)
            )
        );
    }

    /**
     * Get a resource owner by name.
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
     * Given a request with an authorization code, fetches a token, retrieves a user resource id
     * Then, a link is saved between the logged-in user and the resource owner
     *
     * On failure or success, redirect to the target path with a flash notice
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function finishConnecting(Request $request)
    {
        $targetPath = $request->query->get($this->container->getParameter('hwi_oauth.target_path_parameter'), '/');
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $request->getSession();

        // Delete any previous flashes to prevent clutter in case the endpoint isn't consuming them
        $session->getFlashBag()->clear();

        $resourceOwnerName = $session->get('social_connect_resource_owner');
        $message = sprintf('You have failed to connect to your %s account!', ucfirst($resourceOwnerName));

        try
        {
            $resourceOwner = $this->getResourceOwnerByName($resourceOwnerName);
        }
        catch (\RuntimeException $e)
        {
            $session->getFlashBag()->add('notice', $message);

            return $this->redirect($targetPath);
        }

        // Remove the resourceOwnerName if we've retrieved it successfully
        $session->remove('social_connect_resource_owner');

        $apiUser = $this->getUser()->getAPIUser();

        if ($session->has('social_connect_ez_user_id') && $apiUser->id == $session->get('social_connect_ez_user_id'))
        {
            $session->remove('social_connect_ez_user_id');

            // The redirect URL points to the endpoint that requests the access token
            $redirectUrl = $this->generateUrl('netgen_finish_connecting', array(), UrlGeneratorInterface::ABSOLUTE_URL);

            $token = $resourceOwner->getAccessToken($request, $redirectUrl);

            $userInformation = $resourceOwner->getUserInformation($token);

            if ($userInformation instanceof PathUserResponse)
            {
                $resourceUserId = $userInformation->getResponse()['id'];

                $loginHelper = $this->get('netgen.social_connect.helper');

                if (empty($loginHelper->loadFromTableByResourceUserId($resourceUserId, $resourceOwnerName)))
                {
                    $loginHelper->addToTable(
                        $loginHelper->loadEzUserById($apiUser->id),
                        $this->getOAuthEzUser($apiUser->login, $resourceOwner->getName(), $resourceUserId),
                        true
                    );
                    $message = sprintf('You have connected to your %s account!', ucfirst($resourceOwnerName));
                }
                else
                {
                    $message = sprintf('This %s account is already connected to another user!', ucfirst($resourceOwnerName));
                }
            }
        }

        $session->getFlashBag()->add('notice', $message);

        return $this->redirect($targetPath);
    }

    /**
     * Wrapper for intermediary OAuthEzUser object for addToTable call
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
}
