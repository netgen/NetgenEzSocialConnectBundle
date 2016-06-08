<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Exception;

use Exception;

class ResourceOwnerNotSupportedException extends Exception
{
    /**
     * Constructor.
     *
     * @param string $resourceName
     */
    public function __construct($resourceName)
    {
        parent::__construct("Current user is not connected to '$resourceName'.");
    }
}
