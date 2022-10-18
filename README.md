# HOME middleware project

This project contains a module for Drupal 8+, that acts as a middleware for accessing the HOME project API.

## Quick start

In order to install this module, you have to:
  - download the files and add them in your site's `web/modules/custom/home_api_middleware` directory
  - or install it via composer `composer require euf/home_api_middleware`

Once installed, enable the module in Drupal on the admin ui or if you have Drush, type `drush en home_api_middleware`

## Setting up the module

You'll have to put the HOME API endpoint URLs and credentials into your `local.settings.php`. By default you can find the settings file in the `web/sites/default/local.settings.php`. Paste the following values to the end of that file
  - `$settings['home_api']['login']['base_uri']`: The base URL of the authentication endpoint. Example: `https://login.home.eu`
  - `$settings['home_api']['login']['path']`: The subpath of the athentication endpoint. Example: `/login`. This means, the full path of the authentication endpoint will be: `https://login.home.eu/login`
  - `$settings['home_api']['credentials']['username']`: The username to log in with on the authentication endpoint.
  - `$settings['home_api']['credentials']['password']`: The password to use with the username on the authentication endpoint.
  - `$settings['home_api']['inventory']['base_uri']`: The base URL of the HOME inventory API endpoint. Example: `https://inventory.home.eu`
  - `$settings['home_api']['inventory']['path']`: The subpath of the HOME inventory API endpoint. Example: `/inventory`. This means, the full path of the inventory endpoint will be: `https://inventory.home.eu/inventory`
