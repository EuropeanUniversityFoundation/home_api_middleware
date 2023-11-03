# HOME middleware project

This project contains a module for Drupal 9+, that acts as a middleware for accessing the HOME project API.

## Quick start

We recommend installing this module via composer. To achieve this, do the following:
  - Add the vcs to the composer.json file in the repositories section, after the existing vcs entries:
    ```
    {
      ...
      "repositories": [
        ...

        {"type": "vcs", "url": "https://github.com/EuropeanUniversityFoundation/home_api_middleware/"},
      ],
      ...
    }
    ```
  - Run `composer require euf/home_api_middleware`
  - Once installed, enable the module in Drupal on the admin ui or if you have Drush, type `drush en home_api_middleware`

## Setting up the module

Edit your `settings.php` or `settings.local.php` if in use and add the following values:
  - `$settings['home_api']['login']['base_uri']`: The base URL of the authentication endpoint.
  - `$settings['home_api']['login']['path']`: The subpath of the athentication endpoint. The full path will be `base_uri/path`
  - `$settings['home_api']['credentials']['username']`: The username to log in with on the authentication endpoint.
  - `$settings['home_api']['credentials']['password']`: The password to use with the username on the authentication endpoint.
  - `$settings['home_api']['base_uri']`: The base URL of the HOME inventory API endpoint.
  - `$settings['home_api']['inventory']['path']`: The subpath of the HOME inventory API endpoint
  - `$settings['home_api']['providers']['path']`: The subpath of the HOME accommodation providers API endpoint.
  - `$settings['home_api']['quality_labels']['path']`: The subpath of the HOME accommodation quality labels API endpoint.

 Full example of the settings file:
 ```
 ...
 /* HOME API settings */
$settings['home_api']['login']['base_uri'] = 'http://login.example.com';
$settings['home_api']['login']['path'] = '/login';
$settings['home_api']['base_uri'] = 'http://api.example.com';
$settings['home_api']['inventory']['path'] = '/inventory';
$settings['home_api']['providers']['path'] = '/housingProviders';
$settings['home_api']['quality_labels']['path'] = '/qualityLabels';
$settings['home_api']['credentials']['username'] = 'example@example.com';
$settings['home_api']['credentials']['password'] = 'example_password';
...
 ```

## Endpoints
The module adds three endpoints to the site, that uses the credentials, urls and paths to first login to the HOME API middleware, store the JWT token and it's expiry in temporary storage and then call the HOME API's corresponding endpoint using the retrieved token to fetch data.

### Inventory endpoint
  - Path: `/accommodation/inventory`
  - Method: `GET`
  - Query parameters: (string) `city`, (int) `page`
  - Example usage: `{site_url}/accommodation/inventory?city=Brussels&page=2`
  - City parameter is required, but can be an empty string. Page parameter is optional.

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
The module provides the `use home api middleware` permission, assign it to ALL AUTHENTICATED users for the accommodation module to work properly. The client has to take care of authenticating the Drupal users. Currently `cookie` authentication is enabled for the endpoint (in the routing file).
