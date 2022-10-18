<?php

namespace Drupal\home_api_middleware\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Site\Settings;

/**
 * Middleware for the HOME API.
 */
class HomeApiMiddlewareController extends ControllerBase {

  /**
   * Guzzle Client for authenticating.
   *
   * @var GuzzleHttp\Client
   */
  protected $authClient;

  /**
   * Login crendetials to HOME API.
   *
   * @var array
   */
  private $credentials;

  /**
   * Guzzle Client for forwarding request.
   *
   * @var GuzzleHttp\Client
   */
  protected $client;

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
   * Indicates if a second attempt to login is allowed and has not happened.
   *
   * @var bool
   */
  private $secondAttempt = TRUE;

  /**
   * Error details.
   *
   * @var array
   */
  private $error;

  /**
   * Constructor.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, Settings $settings) {
    $this->settings = $settings;
    $this->authClient = new Client([
      'base_uri' => $this->settings->get('home_api')['login']['base_uri'],
    ]);
    $this->credentials = [
      'username' => $this->settings->get('home_api')['credentials']['username'],
      'password' => $this->settings->get('home_api')['credentials']['password'],
    ];

    $this->client = new Client([
      'base_uri' => $this->settings->get('home_api')['inventory']['base_uri'],
    ]);

    // Creates or retrieves SharedTempStore.
    $this->tempStoreFactory = $temp_store_factory;
    $this->tempStore = $this->tempStoreFactory->get('home_api_middleware');

    // Sets token data saved in tempStore as class properties.
    $this->expire = $this->tempStore->get('expire');
    $this->token = $this->tempStore->get('token');
  }

  /**
   * Gets tempstore from the container for the Controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.shared'),
      $container->get('settings')
    );
  }

  /**
   * Entry point of the route controller.
   */
  public function handleRequest(Request $request): JsonResponse {
    if (!$this->tokenValid()) {
      $response = $this->getToken();
    }

    if ($this->error) {
      return new JsonResponse($this->error);
    }
    $response = $this->sendRequest($request);

    if ($this->error) {
      if ($this->error['status_code'] == 401 && $this->secondAttempt) {
        $this->secondAttempt = FALSE;
        $this->handleRequest($request);
      }
      return new JsonResponse($this->error);
    }

    return new JsonResponse(json_decode($response->getBody()->getContents()));
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
   * Fetches token from HOME login endpoint.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Returns the response.
   */
  protected function getToken(): Response {
    $options = [
      'json' => $this->credentials,
      // 'http_errors' => FALSE,
    ];
    $path = $this->settings->get('home_api')['login']['path'];

    try {
      $response = $this->authClient->request('POST', $path, $options);
    }
    catch (ClientException | ServerException $e) {
      $this->setError($e);
      return $e->getResponse();
    }

    $body = json_decode($response->getBody()->getContents());
    $this->tempStore->set('expire', $body->expire);
    $this->expire = $body->expire;
    $this->tempStore->set('token', $body->token);
    $this->token = $body->token;

    return $response;
  }

  /**
   * Creates and sends the request to HOME API.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The original Symfony request.
   *
   * @return array
   *   The body of the JSON response as an array.
   */
  protected function sendRequest(Request $request): Response {
    $query = $request->query->all();
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->token,
      ],
      'json' => $query,
      // 'http_errors' => FALSE,
    ];
    $path = $this->settings->get('home_api')['inventory']['path'];
    try {
      $response = $this->client->request('POST', $path, $options);
    }
    catch (ClientException | ServerException $e) {
      $this->setError($e);
      return $e->getResponse();
    }

    return $response;
  }

  /**
   * Gets exception from response.
   */
  protected function setError($exception) {
    $message = $exception->getMessage();
    $status_code = $exception->getResponse()->getStatusCode();

    $this->error = [
      'message' => $message,
      'status_code' => $status_code,
    ];
  }

}
