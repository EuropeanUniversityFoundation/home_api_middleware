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
   * Turns the incoming GET request to a POST request and forwards it.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The original GET request.
   * @param string $path
   *   Subpath to send request to.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Response from HOME API.
   */
  public function handleRequest(Request $request, string $path = NULL): JsonResponse {
    $path = $this->settings->get('home_api')['inventory']['path'];

    $query = json_encode($request->query->all());
    $postRequest = new Request(content: $query);
    $postRequest->setMethod('POST');

    $response = parent::handleRequest($postRequest, $path);

    return $response;
  }

}
