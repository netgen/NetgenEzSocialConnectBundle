<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Exception;

use Exception;

class MissingConfigurationException extends Exception
{
    /**
     * Constructor
     *
     * @param string $configName
     */
    public function __construct( $paramName )
    {
        parent::__construct( "Siteaccess parameter '$paramName' from 'netgen_social_connect' namespace is missing or has empty value." );
    }
}