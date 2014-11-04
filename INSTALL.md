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
            client_id: %facebook.client_id%
            client_secret: %facebook.secret%
            scope: "email"
services:
    hwi_oauth.user.provider.entity:
        class: HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider
---------------------------


/ezpublish/config/parameters.yml
-------------------------------
facebook.client_id: <facebook_client_id>
facebook.secret: <facebook_secret>
netgen.oauth.user_group:
    facebook: 11
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
                providers: [ezpublish, oautha]
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

