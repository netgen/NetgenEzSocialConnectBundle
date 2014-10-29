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
            client_id: 808033489246752
            client_secret: 5b3abc2ae6c0eff9cd9d89bc470a37b0
            scope: "email"
    services:
        hwi_oauth.user.provider.entity:
            class: HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider
---------------------------


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
                providers: [oauth, ezpublish]
        ezpublish:
            id: ezpublish.security.user_provider
        oauth:
            id: hwi_oauth.user.provider.entity
    firewalls:
        firewall_name:
            oauth:
                resource_owners:
                    facebook: /login/login_facebook
                login_path: /login
                check_path: /login_check
                failure_path: /login
                oauth_user_provider:
                    service: netgen.oauth.user_provider
            anonymous: ~
-------------------------------


/ezpublish/config/parameters.yml
-------------------------------
netgen.oauth.user_group: 11
-------------------------------


in template:
-------------------------------
{% include 'NetgenEzSocialConnectBundle:social:social_buttons.html.twig' with { owners: hwi_oauth_resource_owners() } %}
-------------------------------

