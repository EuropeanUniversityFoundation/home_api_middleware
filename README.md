# HOME middleware project

This project contains a module for Drupal 9+, that acts as a middleware for accessing the HOME project API.

## Quick start

We recommend installing this module via composer. To achieve this, do the following:
  - Add the vcs to the composer.json file in the repositories section, aftwer the existing entries:
    ```
    {
      ...
      "repositories": [
        ...
      
        {"type": "vcs", "url": "https://github.com/EuropeanUniversityFoundation/home_api_middleware/"},
      ],
    ...
    ```
  - Run `composer require euf/home_api_middleware`
  - Once installed, enable the module in Drupal on the admin ui or if you have Drush, type `drush en home_api_middleware`

## Setting up the module

You'll have to put the HOME API endpoint URLs and credentials into your `local.settings.php`. By default you can find the settings file in the `web/sites/default/local.settings.php`. Paste the following values to the end of that file
  - `$settings['home_api']['login']['base_uri']`: The base URL of the authentication endpoint. Example: `https://login.home.eu`
  - `$settings['home_api']['login']['path']`: The subpath of the athentication endpoint. Example: `/login`. This means, the full path of the authentication endpoint will be: `https://login.home.eu/login`
  - `$settings['home_api']['credentials']['username']`: The username to log in with on the authentication endpoint.
  - `$settings['home_api']['credentials']['password']`: The password to use with the username on the authentication endpoint.
  - `$settings['home_api']['base_uri']`: The base URL of the HOME inventory API endpoint. Example: `https://inventory.home.eu`
  - `$settings['home_api']['inventory']['path']`: The subpath of the HOME inventory API endpoint. Example: `/inventory`. This means, the full path of the inventory endpoint will be: `https://inventory.home.eu/inventory`
  - `$settings['home_api']['providers']['path']`: The subpath of the HOME accommodation providers API endpoint. Example `/providers`;
  - `$settings['home_api']['quality_labels']['path']`: The subpath of the HOME accommodation quality labels API endpoint. Example `/labels`;

## Endpoints
The module adds three endpoints to the site, that uses the credentials, urls and paths to first login to the HOME API middleware, store the JWT token and it's expiry in temporary storage and then call the HOME API's corresponding endpoint using the retrieved token to fetch data.

### Inventory endpoint
  - Path: `/accommodation/inventory`
  - Method: `GET`
  - Parameters: `city`
  - Example usage: `{site_url}/accommodation/inventory?city=Brussels`
  - This parameter is required!

### Providers endpoint
  - Path: `/accommodation/providers`
  - Method: `GET`
  - Parameters: None
  - Example usage: `{site_url}/accommodation/providers`

### Quality labels endpoint
  - Path: `/accomodation/quality-labels`
  - Method: `GET`
  - Parameters: None
  - Example usage: `{site_url}/accomomdation/quality-labels`

## Permissions and Authentication
The module provides the `use home_api_middleware` permission, assign it to the roles that should be able to access the endpoint. The client has to take care of authenticating the Drupal users. Currently `api_key` and `cookie` authentication is enabled for the endpoint (in the routing file).
