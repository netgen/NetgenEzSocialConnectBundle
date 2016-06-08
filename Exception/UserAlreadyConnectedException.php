<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Exception;

use Exception;

class UserAlreadyConnectedException extends Exception
{
    /**
     * Constructor.
     *
     * @param string $resourceName
     */
    public function __construct($resourceName)
    {
        parent::__construct("Current user is already connected to '$resourceName'.");
    }
}
