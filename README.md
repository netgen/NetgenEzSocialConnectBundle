# NetgenEzSocialConnectBundle
As the impact of social media grows, most sites like to offer login via social networks, be it Facebook, Twitter, or any other.
This bundle makes use of the [HWIOAuthBundle](https://github.com/hwi/HWIOAuthBundle) to provide the ability to log in to eZPublish based sites via social networks.

## Installation
For installation instructions, see [INSTALL.md](https://github.com/netgen/NetgenEzSocialConnectBundle/blob/master/INSTALL.md)

## Features
* When logging in with social network for the first time, it creates new ezuser
* Fetches most important data (if available) - username, real name, email, profile picture
* It is possible to easily connect existing users to their social network account
* User group in which to save the newly created user is defined per siteaccess
* Social network client ids and keys can also be defined per siteaccess (limited supported)

## Known issues
* In order to enable siteaccess specific client ids and keys, resource owner entity needs to be overriden from the HWIOAuthBundle. For now, we only support Facebook, Google, Linkedin and Twitter.
* The bundle is based on HWIOAuthBundle 0.3, due to the fact that the 0.4 is yet to be tagged. This means only Symfony <2.7 is supported.

## Copyright
* Copyright (C) 2015 Netgen. All rights reserved.

## License
* http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2