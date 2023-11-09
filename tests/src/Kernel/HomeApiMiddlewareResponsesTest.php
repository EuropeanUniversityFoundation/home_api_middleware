<?php

namespace Drupal\Tests\home_api_middleware\Kernel;

use Drupal\Tests\home_api_middleware\Kernel\HomeApiMiddlewareTestBase;

/**
 * Test description.
 *
 * @group home_api_middleware
 */
class HomeApiMetricsEndpointsTest extends HomeApiMiddlewareTestBase {

  /**
   * Tests if status code is 200 and response is JSON.
   *
   * @return void
   *   Void.
   */
  public function testInventoryResponse() {
    $request = $this->createRequest('/accommodation/inventory?city=Budapest', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 200);
    $this->assertJson($response->getContent());
  }

  /**
   * Tests if status code is 200 and response is JSON.
   *
   * @return void
   *   Void.
   */
  public function testProviderResponse() {
    $request = $this->createRequest('/accommodation/providers', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 200);
    $this->assertJson($response->getContent());
  }

  /**
   * Tests if status code is 200 and response is JSON.
   *
   * @return void
   *   Void.
   */
  public function testQualityLabelsResponse() {
    $request = $this->createRequest('/accommodation/quality-labels', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 200);
    $this->assertJson($response->getContent());
  }

}
