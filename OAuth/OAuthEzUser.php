<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUser;

class OAuthEzUser extends OAuthUser
{
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $resourceOwner;
    protected $imageLink;

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

    public function setImageLink( $imageLink )
    {
        $this->imageLink = $imageLink;
    }

    public function getImageLink()
    {
        return $this->imageLink;
    }

}