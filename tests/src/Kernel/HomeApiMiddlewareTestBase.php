<?php

namespace Drupal\Tests\home_api_middleware\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test description.
 *
 * @group home_api_middleware
 */
class HomeApiMiddlewareTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'home_api_middleware',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Creates request.
   */
  public function createRequest(string $uri, string $method, array $document = []): Request {
    $request = Request::create($uri, $method, [], [], [], [], $document ? Json::encode($document) : NULL);
    $request->headers->set('Accept', 'application/vnd.api+json');

    return $request;
  }

  /**
   * Handles a request with the http_kernel service.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A preconstructed request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The returned response.
   */
  public function processRequest(Request $request)/*: JsonResponse*/ {
    return $this->container->get('http_kernel')->handle($request);
  }

}
