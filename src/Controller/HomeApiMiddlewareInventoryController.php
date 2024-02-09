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
  // Temporary. Adds default page size for middleware pagination.
  private const DEFAULT_PAGE_SIZE = 20;

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
      if ($request->query->has('page')) {
        $requestedPage = $request->query->get('page');
        // Removes the page parameter from the query.
        $request->query->remove('page');
      }
      else {
        $requestedPage = 1;
      }
    }
    else {
      $requestedPage = 1;
    }

    if (!is_int($requestedPage)) {
      $requestedPage = 1;
    }

    $data = $this->fetchAllData($request, $path);
    $data = $this->removeDuplicateListings($data);
    $data = $this->addPaginationData($data, $requestedPage);
    $data = $this->applyPagination($data, $requestedPage);

    return new JsonResponse($data);
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
   * Removes duplicate listings from the aggregated response data.
   *
   * @param array $data
   *   The input array containing unfiltered listings.
   *
   * @return array
   *   The array with duplicate listings removed.
   */
  public function removeDuplicateListings(array $data): array {
    $ids = [];
    if (!is_null($data['listings'])) {
      foreach ($data['listings'] as $key => $listing) {
        if (in_array($listing->id, $ids)) {
          unset($data['listings'][$key]);
          continue;
        }
        else {
          $ids[] = $listing->id;
        }
      }

      $data['listings'] = array_values($data['listings']);
    }

    return $data;
  }

  /**
   * Fetches all data across all pages of the resulting collection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param mixed $path
   *   Path in the HOME API.
   *
   * @return array
   *   An array containing the metadata, empty pagination data and listings.
   */
  public function fetchAllData(Request $request, $path): array {
    if ($request->getMethod() == 'POST') {
      $postRequest = $request;
    }
    else {
      $postRequest = $this->transformRequest($request);
    }

    $response = parent::handleRequest($postRequest, $path);
    $content = json_decode($response->getContent());

    $pageCount = $content->pagination->totalPages;
    $listingData = $content->listings;
    $metaData = $content->metadata;

    if (!is_null($listingData) && $pageCount > 1) {
      for ($i = 2; $i <= $pageCount; $i++) {
        if ($request->getMethod() == 'GET') {
          $request->query->set('page', $i);
          $postRequest = $this->transformRequest($request);
          $response = parent::handleRequest($postRequest, $path);
          $pageListings = json_decode($response->getContent())->listings;
          $listingData = array_merge($listingData, $pageListings);
        }
        else {
          $postRequest = $this->rebuildPostRequest($postRequest, $i);
          $response = parent::handleRequest($postRequest, $path);
          $pageListings = json_decode($response->getContent())->listings;
          $listingData = array_merge($listingData, $pageListings);
        }
      }
    }

    return [
      'metadata' => $metaData,
      'pagination' => [],
      'listings' => $listingData,
    ];
  }

  /**
   * Adds pagination data to the merged array.
   *
   * @param array $data
   *   Array containing metadata and listings.
   * @param int $requestedPage
   *   The requested page number for pagination.
   *
   * @return array
   *   The array with pagination data added.
   */
  public function addPaginationData(array $data, int $requestedPage = 1): array {
    if (!is_null($data['listings'])) {
      $listingCount = count($data['listings']);
      $totalPages = ceil($listingCount / self::DEFAULT_PAGE_SIZE);
    }
    else {
      $totalPages = 0;
      $listingCount = 0;
    }

    $data['pagination'] = [
      'page' => $requestedPage,
      'size' => self::DEFAULT_PAGE_SIZE,
      'totalPages' => $totalPages,
      'totalListings' => $listingCount,
    ];

    return $data;
  }

  /**
   * Applies pagination to the data array containing the listings.
   *
   * @param array $data
   *   The data array with the listings to be paginated.
   * @param int $requestedPage
   *   The requested page number for pagination.
   *
   * @return array
   *   The data array with pagination applied
   */
  public function applyPagination(array $data, int $requestedPage = 1): array {
    if (!is_null($data['listings'])) {
      $data['listings'] = array_slice($data['listings'], ($requestedPage - 1) * self::DEFAULT_PAGE_SIZE, self::DEFAULT_PAGE_SIZE);
    }

    if (empty($data['listings'])) {
      $data['listings'] = NULL;
    }

    return $data;
  }

  /**
   * A function to rebuild a post request with a new page number.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The original request object.
   * @param int $pageNumber
   *   The new page number for the post request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The rebuilt request object.
   */
  public function rebuildPostRequest(Request $request, int $pageNumber): Request {
    $content = json_decode($request->getContent());
    $content->page = $pageNumber;

    $request->initialize(
      [],
      $request->request->all(),
      $request->attributes->all(),
      $request->cookies->all(),
      $request->files->all(),
      $request->server->all(),
      json_encode($content)
    );

    return $request;
  }

}
