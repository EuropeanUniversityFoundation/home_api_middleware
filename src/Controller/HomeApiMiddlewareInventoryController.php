<?php

namespace Drupal\home_api_middleware\Controller;

use Drupal\home_api_middleware\Controller\AbstractHomeApiMiddlewareController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Middleware for the HOME API.
 */
class HomeApiMiddlewareInventoryController extends AbstractHomeApiMiddlewareController {

  /**
   * Transforms the incoming GET request to a POST request and forwards it.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The original GET request.
   * @param string $path
   *   Subpath to send request to.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response from HOME API.
   */
  public function handleRequest(Request $request, string $path = NULL): JsonResponse {
    $path = $this->settings->get('home_api')['inventory']['path'];

    // If the request has already been transformed to a POST, it should not be transformed again.
    if ($request->getMethod() == 'GET') {
      $request = $this->transformRequest($request);
    }

    $response = parent::handleRequest($request, $path);

    return $response;
  }

  /**
   * Transforms the GET request into a POST request.
   *
   * Adds the city parameter to the new request if missing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The original GET request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The transformed POST request.
   */
  public function transformRequest(Request $request): Request {
    $query = $request->query->all();

    if (!array_key_exists('city', $query)) {
      $query['city'] = '';
    }
    $query = $this->processQueryParameters($query);

    $query = json_encode($query);
    $postRequest = new Request(content: $query);
    $postRequest->setMethod('POST');

    return $postRequest;
  }

}
