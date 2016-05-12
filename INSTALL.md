# INSTALLATION INSTRUCTIONS

As this bundle uses HWIOAuthBundle, installation follows pretty much the same procedure.

# Add bundle to the project via composer
```
    ...
    "netgen/ez-social-connect": "~0.2",
    ...
```

# Enable the bundle in the kernel
# INSTALLATION INSTRUCTIONS

As this bundle uses HWIOAuthBundle, installation is pretty much similar.

# Add HWIOAuthBundle and NetgenEzSocialConnectBundle to the project via composer
```
// ezpublish/EzPublishKernel.php

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

# Update the database
```
php ezpublish/console doctrine:schema:update --force
```
This will add ngsocialconnect table to the database.

# Import the routing
```
# ezpublish/config/routing.yml

_netgen_ez_social_login:
    resource: "@NetgenEzSocialConnectBundle/Resources/config/routing.yml"
    prefix:   /login
```

# Configure resource owners (social networks)
```
# ezpublish/config/config.yml

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
```

# Configure which resource owners can use siteaccess specific parameters
If useConfigResolver option is not set, resource owner will use default parameters.
```
# ezpublish/config/config.yml

netgen_social_connect:
    resource_owners:
        facebook: { useConfigResolver: true }
        twitter: { useConfigResolver: true }            
```            

# Define user content object mappings for social registration
If these parameters are not set, the fields in question will not be mapped to the OAuth resource owner's response.

If the parameter 'merge_social_accounts' set to true, the eZUserProvider will ensure that social users with the same email are tied to the same eZ user.
Otherwise, multiple eZ users will be created, each linked to one social account. A new eZ user with a dummy email will always be created for users not disclosing their email.

```
# ezpublish/config/config.yml
netgen__social_connect:
    system:
        default:
            merge_social_accounts: true
            user_content_class_identifier: user
            fields:
	        first_name: 'first_name'
                last_name: 'last_name'
                profile_image: 'image'
        administration_group:
	    user_content_class_identifier: enhanced_user
            fields:
                first_name: 'intro'
                last_name: ~            # do not import social data to this field
                profile_image: 'picture'
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

# Set up the parameters
Set the id and key for each of the networks you wish to use.
Also, define the user group where the new users should be created.
```
# ezpublish/config/parameters.yml

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
```

# Include the template
Last step is to include the template with social buttons in your login template.
You can ofcourse use your own template, based on this one.
```
{% include 'NetgenEzSocialConnectBundle:social:social_buttons.html.twig' %}
```

# Connecting exiting users
If you would like your existing users to be able to connect their ez account to the social network, so they would in future be able to log in with social network account, simply include another template on the profile page:
```
{% include 'NetgenEzSocialConnectBundle:social:connect_user.html.twig' %}
```


