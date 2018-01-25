# NetgenEzSocialConnectBundle

[![Build Status](https://img.shields.io/travis/netgen/NetgenEzSocialConnectBundle.svg?style=flat-square)](https://travis-ci.org/netgen/NetgenEzSocialConnectBundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/netgen/NetgenEzSocialConnectBundle.svg?style=flat-square)](https://codecov.io/gh/netgen/NetgenEzSocialConnectBundle)
[![Downloads](https://img.shields.io/packagist/dt/netgen/ez-social-connect.svg?style=flat-square)](https://packagist.org/packages/netgen/ez-social-connect/stats)
[![Latest stable](https://img.shields.io/packagist/v/netgen/ez-social-connect.svg?style=flat-square)](https://packagist.org/packages/netgen/ez-social-connect)
[![License](https://img.shields.io/github/license/netgen/NetgenEzSocialConnectBundle.svg?style=flat-square)](LICENSE)

As the impact of social media grows, most sites like to provide login via social networks like Facebook and Twitter.
This bundle makes use of [HWIOAuthBundle](https://github.com/hwi/HWIOAuthBundle) to provide the ability to log in to eZPublish-based sites via social networks.

## Features
* A new eZ user is created when logging in with social network for the first time.
* The following data is fetched (if available) - user id, real name, email, profile picture.
* Existing users can be linked to and unlinked from their social network account easily.
* All relevant configuration can be defined per siteaccess.

## Known issues
* The resource owner entity needs to be overriden from HWIOAuthBundle to enable siteaccess-specific client ids and keys.
  For now, we only support Facebook, Google, Linkedin and Twitter.
* The bundle is based on HWIOAuthBundle 0.4, which supports Symfony >= 2.3, < 3.0. Symfony 3 is not supported yet.

## License, docs and installation instructions

[License](LICENSE)

[Installation instructions](doc/INSTALL.md)

[Changelogs](doc/CHANGELOG-1.x.md)

## Copyright
* Copyright (C) 2018 Netgen. All rights reserved.
