<?php

namespace Netgen\Bundle\EzSocialConnectBundle\OAuth;

class OAuthEzUser
{
    /** @var  string  */
    protected $username;
    /** @var  string */
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
     * @param string $id       Original user id from resource
     */
    public function __construct($username, $id)
    {
        $this->username = $username;
        $this->originalId = $id;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $id
     */
    public function setOriginalId($id)
    {
        $this->originalId = $id;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getOriginalId()
    {
        return $this->originalId;
    }

    /**
     * @param $name
     */
    public function setFirstName($name)
    {
        $this->first_name = $name;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param $surname
     */
    public function setLastName($surname)
    {
        $this->last_name = $surname;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $resourceOwnerName
     */
    public function setResourceOwnerName($resourceOwnerName)
    {
        $this->resourceOwnerName = $resourceOwnerName;
    }

    /**
     * @return string
     */
    public function getResourceOwnerName()
    {
        return $this->resourceOwnerName;
    }

    /**
     * @codeCoverageIgnore
     * 
     * @param $imageLink
     */
    public function setImageLink($imageLink)
    {
        $this->imageLink = $imageLink;
    }

    /**
     * @return string
     */
    public function getImageLink()
    {
        return $this->imageLink;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
}
