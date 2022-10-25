<?php

namespace Drupal\home_api_middleware;

use GuzzleHttp\Client;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Site\Settings;

/**
 * Service for managing HOME API authentication.
 */
class HomeApiMiddlewareAuthenticationManager {

  /**
   * Login crendetials to HOME API.
   *
   * @var array
   */
  private $credentials;

  /**
   * Guzzle Client for authenticating.
   *
   * @var GuzzleHttp\Client
   */
  protected $authClient;

  /**
   * Drupal SharedTempStoreFactory.
   *
   * @var Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Drupal SharedTempStore created by Factory.
   *
   * @var Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * JWT Token for housing anywhere.
   *
   * @var string
   */
  private $token;

  /**
   * Expiry of the JWT token.
   *
   * @var string
   */
  private $expire;

  /**
   * Constructor.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, Settings $settings) {
    $this->settings = $settings;
    $this->credentials = [
      'username' => $this->settings->get('home_api')['credentials']['username'],
      'password' => $this->settings->get('home_api')['credentials']['password'],
    ];

    $this->authClient = new Client([
      'base_uri' => $this->settings->get('home_api')['login']['base_uri'],
    ]);

    // Creates or retrieves SharedTempStore.
    $this->tempStoreFactory = $temp_store_factory;
    $this->tempStore = $this->tempStoreFactory->get('home_api_middleware');

    // Sets token data saved in tempStore as class properties.
    $this->expire = $this->tempStore->get('expire');
    $this->token = $this->tempStore->get('token');
  }

  /**
   * Fetches token from HOME login endpoint.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Returns the response.
   */
  public function getToken($force_renew = FALSE): array {
    if ($this->tokenValid() && !$force_renew) {
      return [
        'token' => $this->token,
        'expire' => $this->expire,
      ];
    }

    $options = [
      'json' => $this->credentials,
    ];
    $path = $this->settings->get('home_api')['login']['path'];

    try {
      $response = $this->authClient->request('POST', $path, $options);
    }
    catch (ClientException | ServerException $e) {
      $error = $this->getError($e);
      return $error;
    }

    $body = json_decode($response->getBody()->getContents());
    $this->tempStore->set('expire', $body->expire);
    $this->expire = $body->expire;
    $this->tempStore->set('token', $body->token);
    $this->token = $body->token;

    return [
      'token' => $this->token,
      'expire' => $this->expire,
    ];
  }

  /**
   * Checks if token is saved to tempstore and is not expired.
   *
   * @return bool
   *   Returns if saved token should be used.
   */
  protected function tokenValid(): bool {
    $valid =
      !is_null($this->expire) &&
      !is_null($this->token) &&
      \strtotime($this->expire) > time();

    return $valid;
  }

  /**
   * Gets error from exception response.
   *
   * @param \GuzzleHttp\ClientException|\GuzzleHttp\ServerException $exception
   *   Incoming exception.
   *
   * @return array
   *   Array with message and status_code keys.
   */
  protected function getError($exception) {
    $message = $exception->getMessage();
    $status_code = $exception->getResponse()->getStatusCode();

    return [
      'message' => $message,
      'status_code' => $status_code,
    ];
  }

}