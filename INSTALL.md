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
            client_id: %netgen_social_connect.default.facebook.id%
            client_secret: %netgen_social_connect.default.facebook.secret%
            scope: "email"
            infos_url: "https://graph.facebook.com/me?fields=id,name,email,picture.type(large)"
            paths:
                profilepicture: picture.data.url
        twitter:
            type: twitter
            client_id: %netgen_social_connect.default.twitter.id%
            client_secret: %netgen_social_connect.default.twitter.secret%
            scope: "email"
        linkedin:
            type: linkedin
            client_id: %netgen_social_connect.default.linkedin.id%
            client_secret: %netgen_social_connect.default.linkedin.secret%
            scope: "r_emailaddress"
        google:
            type: google
            client_id: %netgen_social_connect.default.google.id%
            client_secret: %netgen_social_connect.default.google.secret%
            scope: "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile"

netgen_ez_social_connect:
    resource_owners:
        facebook: { useConfigResolver: true }
        twitter: { useConfigResolver: true }            
            

services:
    hwi_oauth.user.provider.entity:
        class: HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider
---------------------------

/ezpublish/config/parameters.yml
-------------------------------
parameters:
    netgen_social_connect.default.facebook.id: <facebook_client_id>
    netgen_social_connect.default.facebook.secret: <facebook_secret>
    netgen_social_connect.default.twitter.id: <twitter_client_id>
    netgen_social_connect.default.twitter.secret: <twitter secret>
    netgen_social_connect.default.linkedin.id: <linkedin_client_id>
    netgen_social_connect.default.linkedin.secret: <linkedin_secret>
    netgen_social_connect.default.google.id: <google_client_id>
    netgen_social_connect.default.google.secret: <google_secret>
    netgen_social_connect.default.oauth.user_group:
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

