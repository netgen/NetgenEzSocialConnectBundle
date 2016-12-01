# NetgenEzSocialConnectBundle

[![Build Status](https://img.shields.io/travis/netgen/NetgenEzSocialConnectBundle.svg?style=flat-square)](https://travis-ci.org/netgen/NetgenEzSocialConnectBundle.svg)
[![Code Coverage](https://img.shields.io/codecov/c/github/netgen/NetgenEzSocialConnectBundle.svg?style=flat-square)](https://codecov.io/gh/netgen/NetgenEzSocialConnectBundle)
[![Downloads](https://img.shields.io/packagist/dt/netgen/ez-social-connect.svg?style=flat-square)](https://packagist.org/packages/netgen/ez-social-connect)
[![Latest stable](https://img.shields.io/packagist/v/netgen/ez-social-connect.svg?style=flat-square)](https://packagist.org/packages/netgen/ez-social-connect)
[![License](https://img.shields.io/packagist/l/netgen/ez-social-connect.svg?style=flat-square)](https://packagist.org/packages/netgen/ez-social-connect)

As the impact of social media grows, most sites like to provide login via social networks like Facebook and Twitter.
This bundle makes use of [HWIOAuthBundle](https://github.com/hwi/HWIOAuthBundle) to provide the ability to log in to eZPublish-based sites via social networks.

## Installation
For installation instructions, see [INSTALL.md](https://github.com/netgen/NetgenEzSocialConnectBundle/blob/master/INSTALL.md).

## Features
* A new eZ user is created when logging in with social network for the first time.
* Optionally, users with the same email as the social user can be merged instead, adding a link between eZ and the resource owner.
* The following data is fetched (if available) - user id, real name, email, profile picture.
* Existing users can be linked to and unlinked from their social network account easily.
* All relevant configuration can be defined per siteaccess.

## Known issues
* The resource owner entity needs to be overriden from HWIOAuthBundle to enable siteaccess-specific client ids and keys.
  For now, we only support Facebook, Google, Linkedin and Twitter.
* The bundle is based on HWIOAuthBundle 0.4, which supports Symfony >= 2.3, < 3.0. Symfony 3 is not supported yet.

## Copyright
* Copyright (C) 2016 Netgen. All rights reserved.

## License
* http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
