INSTALL.md:

/ezpublish/EzPublishKernel.php enable bundles
----------------------------
new HWIOAuthBundle(),
new EzSocialConnectBundle\NetgenEzSocialConnectBundle()
----------------------------


/ezpublish/config/config.yml
----------------------------
hwi_oauth:
    # name of the firewall in which this bundle is active, this setting MUST be set
    firewall_name: ezpublish_front
    resource_owners:
        facebook:
            type: facebook
            client_id: %netgen_social_connect.default.facebook_id%
            client_secret: %netgen_social_connect.facebook.secret%
            scope: "email"
        twitter:
            type: twitter
            client_id: %netgen_social_connect.default.twitter_id%
            client_secret: %netgen_social_connect.twitter.secret%
            scope: "email"
        linkedin:
            type: linkedin
            client_id: %netgen_social_connect.default.linkedin_id%
            client_secret: %netgen_social_connect.linkedin.secret%
            scope: "r_emailaddress"
        google:
            type: google
            client_id: %netgen_social_connect.default.google_id%
            client_secret: %netgen_social_connect.google.secret%
            scope: "email   "

services:
    hwi_oauth.user.provider.entity:
        class: HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider
---------------------------

/ezpublish/config/parameters.yml
-------------------------------
parameters:
    netgen_social_connect.default.facebook_id: <facebook_client_id>
    netgen_social_connect.facebook.secret: <facebook_secret>
    netgen_social_connect.default.twitter_id: <twitter_client_id>
    netgen_social_connect.twitter.secret: <twitter secret>
    netgen_social_connect.default.linkedin_id: <linkedin_client_id>
    netgen_social_connect.linkedin.secret: <linkedin_secret>
    netgen_social_connect.default.google_id: <google_client_id>
    netgen_social_connect.google.secret: <google_secret>
    netgen.oauth.user_group:
        facebook: 11
        twitter: 11
        linkedin: 11
        google: 11
-------------------------------


/ezpublish/config/routing.yml
-------------------------------
    _netgen_ez_social_login:
        resource: "@NetgenEzSocialConnectBundle/Resources/config/routing.yml"
        prefix:   /login
-------------------------------


/ezpublish/config/security.yml
-------------------------------
security:
    providers:
        chain_provider:
            chain:
               providers: [ezpublish, oauth]
        ezpublish:
            id: ezpublish.security.user_provider
        oauth:
            id: hwi_oauth.user.provider.entity
    firewalls:
        ezpublish_front:
            oauth:
                provider: oauth
                resource_owners:
                    facebook: facebook_login
                    twitter: twitter_login
                    linkedin: linkedin_login
                    google: google_login
                login_path: /login
                failure_path: /login
                default_target_path: /
                oauth_user_provider:
                    service: netgen.oauth.user_provider
            pattern: ^/
            anonymous: ~
            form_login:
                provider: ezpublish
            logout: ~
-------------------------------

in template:
-------------------------------
{% include 'NetgenEzSocialConnectBundle:social:social_buttons.html.twig' with { owners: hwi_oauth_resource_owners() } %}
-------------------------------

