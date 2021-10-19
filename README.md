# Akamai

https://www.drupal.org/project/akamai

This module provides a Drupal service to interact with the [Akamai Content
Control Utility](https://developer.akamai.com/api/purge/ccu/overview.html).

While the service can be used by developers in isolation, most users should
install the [Purge](http://drupal.org/project/purge) module. Purge will take
care of invalidating caches automatically when content is updated.

It incorporates the
[AkamaiOPEN-edgegrid-php](https://github.com/akamai-open/AkamaiOPEN-edgegrid-php)
library.

## Latest Documentation

Please visit the [Drupal docs for Akamai](https://www.drupal.org/docs/contributed-modules/akamai).

## Installation and configuration

Download the module with drush or otherwise, add it to the `modules` folder.

You will need to download
[akamai-open/edgrid-client](https://packagist.org/packages/akamai-open/edgegrid-
client). The recommended way to do that is by installing [Composer
Manager](https://www.drupal.org/project/composer_manager), and following its
instructions to update your site's `vendor` directory.

### With Purge

Make sure `purge_ui` is enabled.

Go to `admin/config/development/performance/purge` and enable the Akamai Purger
in the list of Purger plugins.

Configure your Akamai credentials via the 'Config' dropdown in the Purge UI
interface.

### Without Purge

Go to `/admin/config/akamai/config` and enter your Akamai credentials.

Go to `/admin/config/akamai/cache-control` to clear URLs manually.

## Akamai Credentials

Follow the instructions here to set up the client credentials.
https://developer.akamai.com/introduction/Prov_Creds.html

You will need admin access to the Luna control panel to create credentials.

### Using an .edgerc File

You can store an `.edgerc` file on the server and reference the path in
/admin/config/akamai/settings. It should match this format:
```
[default]
host = akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net/
access_token = akab-access-token-xxx-xxxxxxxxxxxxxxxx
client_token = akab-client-token-xxx-xxxxxxxxxxxxxxxx
client_secret = xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
max-size = 2048
```

### Using the Key Module

Alternatively, you can use the key module to set API credentials. Create
Authentication keys at /admin/config/system/keys. Then go to
/admin/config/akamai/settings and specify the corresponding key for your
access token, client token, and client secret.

## Usage

### With Purge

You will need to make sure that you have necessary Purge plugins enabled and
configured:

*  a queuer (at present, purge_queuer_url is the only queuer supported by this
module)
*  a queue
*  a processor

Purge will queue URLs that need to be cleared from Akamai automatically.

### Without Purge

There are two ways to clear URLs without Purge:

1. Via the form at `admin/config/akamai/cache-clear`, which allows you to enter
lists of URLs to clear.
2. You can enable a block, 'Akamai Cache Clear', which will allow you to clear
the page you are currently viewing.
