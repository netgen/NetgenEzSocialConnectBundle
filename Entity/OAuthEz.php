<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Entity;

class OAuthEz
{
    /**
     * @var int
     */
    protected $ezUserId;

    /**
     * @var string
     */
    protected $resourceUserId;

    /**
     * @var string
     */
    protected $resourceName;

    /**
     * @var boolean
     */
    protected $disconnectable;

    /**
     * @return bool
     */
    public function isDisconnectable()
    {
        return $this->disconnectable;
    }

    /**
     * @param bool $disconnectable
     */
    public function setDisconnectable($disconnectable)
    {
        $this->disconnectable = $disconnectable;
    }

    /**
     * Set user id from eZ.
     *
     * @param $ezUserId
     *
     * @return \Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz
     */
    public function setEzUserId($ezUserId)
    {
        $this->ezUserId = $ezUserId;

        return $this;
    }

    /**
     * Get eZ Publish user ID.
     *
     * @return int
     */
    public function getEzUserId()
    {
        return $this->ezUserId;
    }

    /**
     * Set user ID from resource.
     *
     * @param int $userOriginalId
     *
     * @return \Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz
     */
    public function setResourceUserId($userOriginalId)
    {
        $this->resourceUserId = $userOriginalId;

        return $this;
    }

    /**
     * Get user resource ID.
     *
     * @return int
     */
    public function getResourceUserId()
    {
        return $this->resourceUserId;
    }

    /**
     * Set resource name (eg. 'facebook').
     *
     * @param string $resourceName
     *
     * @return \Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz
     */
    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * Get resource name (eg. 'facebook').
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }
}
