<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Entity;

class OAuthEz
{
    /**
     * @const bool
     */
    const IS_DISCONNECTABLE = true;

    /**
     * @const bool
     */
    const NOT_DISCONNECTABLE = false;

    /**
     * @var int
     */
    private $ezUserId;

    /**
     * @var string
     */
    private $resourceUserId;

    /**
     * @var string
     */
    private $resourceName;

    /**
     * @var boolean
     */
    private $isDisconnectable;

    /**
     * @return bool
     */
    public function isIsDisconnectable()
    {
        return $this->isDisconnectable;
    }

    /**
     * @param bool $isDisconnectable
     */
    public function setIsDisconnectable($isDisconnectable)
    {
        $this->isDisconnectable = $isDisconnectable;
    }

    /**
     * Set user id from eZ.
     *
     * @param $ezUserId
     *
     * @return OAuthEz
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
     * @return OAuthEz
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
     * @return OAuthEz
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
