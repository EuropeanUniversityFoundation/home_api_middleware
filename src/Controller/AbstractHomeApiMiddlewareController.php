<?php

namespace Drupal\home_api_middleware\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\home_api_middleware\HomeApiMiddlewareAuthenticationManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class reused by all controllers.
 */
abstract class AbstractHomeApiMiddlewareController extends ControllerBase {

  /**
   * HOME API Authentication Manager Service.
   *
   * @var \Drupal\home_api_middleware\HomeApiMiddlewareAuthenticationManager
   */
  protected $authManager;

  /**
   * Guzzle Client for forwarding request.
   *
   * @var GuzzleHttp\Client
   */
  protected $client;

  /**
   * Token retrieved.
   *
   * @var string
   */
  protected $token;

  /**
   * Indicates if a second attempt to login is allowed and has not happened.
   *
   * @var bool
   */
  private $secondAttemptLeft = TRUE;

  /**
   * Settings for the module.
   *
   * @var Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Temporary store factory.
   *
   * @var Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Shared temporary store.
   *
   * @var Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructor.
   */
  public function __construct(
    HomeApiMiddlewareAuthenticationManager $auth_manager,
    Settings $settings,
    SharedTempStoreFactory $temp_store_factory
  ) {
    $this->settings = $settings;
    $this->authManager = $auth_manager;
    $this->client = new Client([
      'base_uri' => $this->settings->get('home_api')['base_uri'],
    ]);
    $this->tempStore = $temp_store_factory->get('home_api_middleware');
  }

  /**
   * Gets services from the container for the Controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('home_api_middleware.authentication_manager'),
      $container->get('settings'),
      $container->get('tempstore.shared')
    );
  }

  /**
   * Handles the requests incoming from separate controllers.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The original request.
   * @param string $path
   *   Subpath to call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response from the HOME API.
   */
  public function handleRequest(Request $request, string $path): JsonResponse {
    $tokenResponse = $this->authManager->getToken(!$this->secondAttemptLeft);

    if (!isset($tokenResponse['token'])) {
      return new JsonResponse($tokenResponse, $tokenResponse['status_code']);
    }
    else {
      $this->token = $tokenResponse['token'];
    }

    $response = $this->forwardRequest($request, $path);

    $status_code = $response->getStatusCode();

    if ($this->tokenNeedsResetting($response)) {
      $this->deleteTokenInTempStore();
      $response = $this->handleRequest($request, $path);
    }
    elseif ($status_code == 200 || $status_code == 201) {
      $response = new JsonResponse(json_decode($response->getBody()), $status_code);
    }
    else {
      $response = new JsonResponse(json_decode($response->getBody()->getContents()), $status_code);
    }

    return $response;
  }

  /**
   * Forwards GET or POST request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The original request.
   * @param string $path
   *   Subpath to call.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Response from the HOME API.
   */
  public function forwardRequest(Request $request, string $path): Response {
    $method = $request->getMethod();

    if ($method == 'GET') {
      $response = $this->sendGetRequest($request, $path);
    }
    elseif ($method == 'POST') {
      $response = $this->sendPostRequest($request, $path);
    }

    return $response;
  }

  /**
   * Sends GET request to HOME API.
   */
  public function sendGetRequest(Request $request, string $path): Response {
    $options = $this->getOptions($request);

    try {
      $response = $this->client->request('GET', $path, $options);
    }
    catch (ClientException | ServerException $e) {

      return $e->getResponse();
    }

    return $response;
  }

  /**
   * Sends POST request to HOME API.
   */
  public function sendPostRequest(Request $request, string $path, int $id = NULL): Response {
    $body = json_decode($request->getContent());

    $options = $this->getOptions($request);
    $options['json'] = $body;

    try {
      $response = $this->client->request('POST', $path, $options);
    }
    catch (ClientException | ServerException $e) {

      return $e->getResponse();
    }

    return $response;
  }

  /**
   * Refreshes the token if response status code is 401.
   */
  public function tokenNeedsResetting(Response $response) {
    return $response->getStatusCode() == 401 && $this->secondAttemptLeft;
  }

  /**
   * Deletes token from temp store.
   */
  public function deleteTokenInTempStore() {
    $this->secondAttemptLeft = FALSE;
    $this->tempStore->delete('expire');
    $this->tempStore->delete('token');
  }

  /**
   * Converts query parameters into numeric if number.
   *
   * @param array $query
   *   The query params in array format.
   *
   * @return array|null
   *   The converted query parameters in array format.
   */
  public function processQueryParameters(array $query): ?array {
    if (!empty($query)) {
      foreach ($query as $name => $value) {
        if (is_numeric($value)) {
          $query[$name] = intval($value);
        }
      }
    }
    else {
      return NULL;
    }

    return $query;
  }

  /**
   * Creates options array for requests.
   */
  protected function getOptions(Request $request) {
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->token,
      ],
    ];

    $query = $request->query->all();
    $query = $this->processQueryParameters($query);

    if (!empty($query)) {
      $options['query'] = $query;
    }

    return $options;
  }

}
