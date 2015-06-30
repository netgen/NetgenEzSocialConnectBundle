<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUser;

class OAuthEzUser extends OAuthUser
{
    /** @var string */
    protected $originalId;
    /** @var  string */
    protected $first_name;
    /** @var  string */
    protected $last_name;
    /** @var  string */
    protected $email;
    /** @var  string */
    protected $resourceOwnerName;
    /** @var  string */
    protected $imageLink;

    /**
     * @param string $username
     * @param string $id original user id from resource
     */
    public function __construct( $username, $id )
    {
        parent::__construct( $username );
        $this->originalId = $id;
    }

    public function setOriginalId( $id )
    {
        $this->originalId = $id;
    }

    public function getOriginalId()
    {
        return $this->originalId;
    }

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

    public function setResourceOwnerName( $resourceOwnerName )
    {
        $this->resourceOwnerName = $resourceOwnerName;
    }

    public function getResourceOwnerName()
    {
        return $this->resourceOwnerName;
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