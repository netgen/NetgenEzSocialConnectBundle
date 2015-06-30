<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Netgen\Bundle\EzSocialConnectBundle\Exception\UserAlreadyConnected;
use Symfony\Component\HttpFoundation\Request;

class ConnectController extends Controller
{
    /**
     * Sets the ez user id into session variable,
     * and starts the connection to the social network
     *
     * @param Request $request
     * @param $resource_name
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws UserAlreadyConnected
     */
    public function connectUser( Request $request, $resource_name )
    {
        $userContentId = $this->getUser()->getAPIUser()->id;

        $loginHelper = $this->get( 'netgen.social_connect.helper' );

        $OAuthEz = $loginHelper->loadFromTableByEzId( $userContentId, $resource_name );

        if( !empty( $OAuthEz ) )
        {
            throw new UserAlreadyConnected( $resource_name );
        }

        $request->getSession()->set( 'social_connect_ez_user_id', $userContentId );
        $request->getSession()->save();

        return $this->redirect( $this->generateUrl( 'hwi_oauth_service_redirect', array( 'service' => $resource_name ) ) );
    }
}