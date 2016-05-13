<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Exception;

use Exception;

class UserNotLoggedInException extends Exception
{
    /**
     * Constructor.
     *
     * @param string $resourceName
     */
    public function __construct($resourceName, $message)
    {
        parent::__construct(sprintf($message, $resourceName));
    }
}
