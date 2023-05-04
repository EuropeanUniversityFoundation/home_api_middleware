<?php

namespace Drupal\home_api_middleware\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\home_api_middleware\HomeApiMiddlewareAuthenticationManager;

/**
 * Middleware for the HOME API.
 */
class HomeApiMiddlewareProviderController extends ControllerBase {

  /**
   * HOME API Authentication MAnager Service.
   *
   * @var \Drupal\home_api_middleware\HomeApiMiddlewareAuthenticationManager
   */
  protected $authManager;

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
   * Constructor.
   */
  public function __construct(
    HomeApiMiddlewareAuthenticationManager $auth_manager,
    Settings $settings,
    SharedTempStoreFactory $temp_store_factory) {
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
   * Handle incoming request for provider endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Original request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function handleRequest(Request $request): JsonResponse {
    $response = $this->authManager->getToken(!$this->secondAttemptLeft);

    if (!isset($response['token'])) {
      return new JsonResponse($response, $response['status_code']);
    }
    else {
      $this->token = $response['token'];
    }

    $response = $this->sendApiRequest($request);

    $status_code = $response->getStatusCode();

    if ($status_code == 401 && $this->secondAttemptLeft) {
      $this->secondAttemptLeft = FALSE;
      $this->tempStore->delete('token');
      $response = $this->handleRequest($request);
    }
    elseif ($status_code == 200) {
      $response = new JsonResponse(json_decode($response->getBody()), $status_code);
    }
    else {
      $response = new JsonResponse(json_decode($response->getBody()->getContents()), $status_code);
    }

    return $response;
  }

  /**
   * Creates and sends the request to HOME API Providers endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Original request.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The received response.
   */
  public function sendApiRequest(Request $request): Response {
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->token,
      ],
    ];

    $path = $this->settings->get('home_api')['providers']['path'];
    try {
      $response = $this->client->request('GET', $path, $options);
    }
    catch (ClientException | ServerException $e) {

      return $e->getResponse();
    }

    return $response;
  }

}
