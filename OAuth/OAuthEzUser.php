<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUser;

class OAuthEzUser extends OAuthUser
{
    private $first_name;
    private $last_name;
    private $email;
    private $resourceOwner;

    public function setFirstName( $name )
    {
        $this->first_name = $name;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function setLastName( $surname )
    {
        $this->last_name = $surname;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function setEmail( $email )
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setResourceOwner( $resourceOwner )
    {
        $this->resourceOwner = $resourceOwner;
    }

    public function getResourceOwner()
    {
        return $this->resourceOwner;
    }

}