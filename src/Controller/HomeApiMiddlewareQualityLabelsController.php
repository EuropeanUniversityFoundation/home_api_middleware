<?php

namespace Drupal\home_api_middleware\Controller;

use Drupal\home_api_middleware\Controller\AbstractHomeApiMiddlewareController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Middleware for the HOME API.
 */
class HomeApiMiddlewareQualityLabelsController extends AbstractHomeApiMiddlewareController {

  /**
   * Handle incoming request for quality labels endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Original request.
   * @param string $path
   *   Subpath to send request to.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function handleRequest(Request $request, $path = NULL): JsonResponse {
    $path = $this->settings->get('home_api')['quality_labels']['path'];

    $response = parent::handleRequest($request, $path);

    return $response;
  }

}
