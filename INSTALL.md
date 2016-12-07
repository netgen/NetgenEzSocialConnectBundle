# INSTALLATION INSTRUCTIONS

This bundle uses HWIOAuthBundle, and the installation process is similar.

# Add bundle to the project via composer
```
    ...
    "netgen/ez-social-connect": "~0.9",
    ...
```

# Register HWIOAuthBundle and NetgenEzSocialConnectBundle in the kernel
```
# ezpublish/EzPublishKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new \HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
        new \Netgen\Bundle\EzSocialConnectBundle\NetgenEzSocialConnectBundle();
        // ...
    );
}
```

# Import the routing
```
# ezpublish/config/routing.yml

_netgen_ez_social_login:
    resource: "@NetgenEzSocialConnectBundle/Resources/config/routing.yml"
    prefix:   /login
```

# Configure bundle-specific parameters - global id/secrets

If we are using the same id/secrets for all siteaccesses), define the HWI id/secret parameters above, and omit the netgen_social_connect id/secrets:


```
# ezpublish/config/config.yml

hwi_oauth:
    # name of the firewall in which this bundle is active, this setting MUST be set
    firewall_name: ezpublish_front
    resource_owners:
        facebook:
            type: facebook
            client_id: "123456789"
            client_secret: "123456789"

# ezpublish/config/config.yml
netgen_social_connect:
    system:
        default:
            user_content_type_identifier: user

            # if these are not set, the fields in question will not be mapped to the OAuth resource owner's response
            # these parameters are fetched using configResolver->getParameter('first_name', 'netgen_social_connect')

            field_identifiers:
                  first_name: 'first_name'
                  last_name: 'last_name'
                  profile_image: 'image'

            resource_owners:
                facebook:
                    user_group: 11

```

# Configure bundle-specific parameters - siteaccess-specific id/secrets

Here's a sample configuration. Any values not present in netgen_social_connect => system in other siteaccesses are taken from 'default'.
Note the line "use_config_resolver: true":

```
# ezpublish/config/config.yml

hwi_oauth:
    # name of the firewall in which this bundle is active, this setting MUST be set
    firewall_name: ezpublish_front
    resource_owners:
        facebook:
            type: facebook
            client_id: _placeholder
            client_secret: _placeholder
            scope: "email"
            infos_url: "https://graph.facebook.com/me?fields=id,name,email,picture.type(large)"
            paths:
                profilepicture: picture.data.url
        twitter:
            type: twitter
            client_id: _placeholder
            client_secret: _placeholder
            scope: "email"
        linkedin:
            type: linkedin
            client_id: _placeholder
            client_secret: _placeholder
            scope: "r_emailaddress"
        google:
            type: google
            client_id: _placeholder
            client_secret: _placeholder
            scope: "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile"

netgen_social_connect:
    resource_owners:
        facebook: { use_config_resolver_: true }
        twitter: { use_config_resolver_: true }
    system:
        default:

            user_content_type_identifier: user

            # if these are not set, the fields in question will not be mapped to the OAuth resource owner's response
            # these parameters are fetched using configResolver->getParameter('first_name', 'netgen_social_connect')

            field_identifiers:
                  first_name: 'first_name'
                  last_name: 'last_name'
                  profile_image: 'image'

            # the following lines set app ids and secrets per siteaccess

            resource_owners:
                facebook:
                    id:         123456789
                    secret:     123456789
                    user_group: 11
                twitter:
                    id:         31415926535
                    secret:     31415926535
                    user_group: 11                    
        administration_group:
            user_content_type_identifier: user_admin

            # if these are not set, the fields in question will not be mapped to the OAuth resource owner's response
            # these parameters are fetched using configResolver->getParameter('first_name', 'netgen_social_connect')

            field_identifiers:
                  first_name: 'firstname'
                  last_name: 'lastname'
                  profile_image: 'profile_image'

            # the following lines set app ids and secrets per siteaccess

            resource_owners:
                facebook:
                    id:         987654321
                    secret:     987654321
                    user_group: 12
```

# Configure the firewall
```
# ezpublish/config/security.yml

security:
    providers:
        chain_provider:
            chain:
               providers: [ezpublish, oauth]
        ezpublish:
            id: ezpublish.security.user_provider
        oauth:
            id: hwi_oauth.user.provider
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
                    service: netgen.social_connect.oauth_user_provider
            pattern: ^/
            anonymous: ~
            form_login:
                provider: ezpublish
            logout: ~
```

# Update the database
```
php ezpublish/console doctrine:schema:update --force
```
This will add the ngsocialconnect table to the database.

# Include the template
The last step is to include the template with social buttons in your login template.
You can, of course, use your own template, based on this one.
```
{% include 'NetgenEzSocialConnectBundle:social:social_buttons.html.twig' %}
```

# Connecting exiting users
If you would like your existing users to be able to connect their ez account to the social network, so they would be able to log in with social network account in the future, simply include another template on the profile page:
```
{% include 'NetgenEzSocialConnectBundle:social:connect_user.html.twig' %}
```
