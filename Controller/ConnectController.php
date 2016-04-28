<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Netgen\Bundle\EzSocialConnectBundle\Exception\UserAlreadyConnected;
use Symfony\Component\HttpFoundation\Request;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use eZ\Publish\Core\MVC\Symfony\Security\UserInterface;

class ConnectController extends Controller
{
    /**
     * Sets the ez user id into session variable,
     * and starts the connection to the social network.
     *
     * @param Request   $request
     * @param string    $resource_name
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws UserAlreadyConnected
     */
    public function connectUser(Request $request, $resource_name)
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
        $request->getSession()->set('social_connect_ez_user_id', $userContentId);
        $request->getSession()->save();

        return $this->redirect(
            $this->generateUrl(
                'hwi_oauth_service_redirect',
                array(
                    'service' => $resource_name,
                    $targetPathParameter => $targetPath,
                )
            )
        );
    }

    /**
     * Removes a link between eZ user and resource user
     *
     * @param Request   $request
     * @param string    $resource_name
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws NotFoundException
     */
    public function disconnectUser(Request $request, $resource_name)
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

        $loginHelper->removeFromTable($OAuthEz);

        $session = $request->getSession();
        $session->getFlashBag()->add('notice', 'You have successfully disconnected user from Facebook account!');

        return $this->redirect(
            $targetPath
        );
    }
}
