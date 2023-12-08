<?php

namespace Drupal\home_api_middleware\Controller;

use Drupal\home_api_middleware\Controller\AbstractHomeApiMiddlewareController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Middleware for the HOME API.
 */
class HomeApiMiddlewareInventoryController extends AbstractHomeApiMiddlewareController {

  // Temporary. Adds default sorting by this attribute to avoid item randomisation.
  private const DEFAULT_SORT_ATTRIBUTE = 'rent';

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

    $response = $this->removeDuplicates($response);

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

    // Adds sorting by price to avoid order randomisation by the HOME API. Temporary.
    if (!array_key_exists('sortBy', $query) || $query['sortBy'] == '') {
      $query['sortBy'] = self::DEFAULT_SORT_ATTRIBUTE;
    }
    $query = $this->processQueryParameters($query);

    $query = json_encode($query);
    $postRequest = new Request(content: $query);
    $postRequest->setMethod('POST');

    return $postRequest;
  }

  /**
   * Removes duplicated items. Temporary.
   *
   * Should be a temporary solution until the API is fixed.
   *
   * @param \Symfony\Component\HttpFoundation\JsonResponse $response
   *   The response from the Controller.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response without the duplicates.
   */
  public function removeDuplicates(JsonResponse $response): JsonResponse {
    $content = json_decode($response->getContent());
    $ids = [];

    foreach ($content->listings as $key => $listing) {
      if (in_array($listing->id, $ids)) {
        unset($content->listings[$key]);
        continue;
      }
      else {
        $ids[] = $listing->id;
      }
    }

    $content->listings = array_values($content->listings);
    $response->setContent(json_encode($content));

    return $response;
  }

}
