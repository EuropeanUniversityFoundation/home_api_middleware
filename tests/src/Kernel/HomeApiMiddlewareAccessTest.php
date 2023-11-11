<?php

namespace Drupal\Tests\home_api_middleware\Kernel;

use Drupal\Tests\home_api_middleware\Kernel\HomeApiMiddlewareTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests access to endpoints.
 *
 * @group home_api_middleware
 */
class HomeApiMiddlewareAccessTest extends HomeApiMiddlewareTestBase {

  use UserCreationTrait;

  /**
   * Switches to Anonymous user.
   *
   *   @see UserCreationTrait
   *
   * @return void
   *   Void.
   */
  private function switchToUserWithoutAccess() {
    $this->setUpCurrentUser(['uid' => 0]);
  }

  /**
   * Tests access as anonymous user.
   *
   * @return void
   *   Void.
   */
  public function testAnonymousAccessInventory() {
    $this->switchToUserWithoutAccess();

    $request = $this->createRequest('/api/accommodation/inventory?city=', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 403);
  }

  /**
   * Tests access as anonymous user.
   *
   * @return void
   *   Void.
   */
  public function testAnonymousAccessProviders() {
    $this->switchToUserWithoutAccess();

    $request = $this->createRequest('/api/accommodation/providers', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 403);
  }

  /**
   * Tests access as anonymous user.
   *
   * @return void
   *   Void.
   */
  public function testAnonymousAccessQualityLabels() {
    $this->switchToUserWithoutAccess();

    $request = $this->createRequest('/api/accommodation/quality-labels', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 403);
  }

}
