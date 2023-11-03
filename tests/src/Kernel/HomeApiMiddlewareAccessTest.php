<?php

namespace Drupal\Tests\home_api_middleware\Kernel;

use Drupal\Tests\home_api_middleware\Kernel\HomeApiMiddlewareTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test description.
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
  public function testNoAnonymousAccess() {
    $this->switchToUserWithoutAccess();

    $request = $this->createRequest('/accommodation/inventory?city=', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 403);
  }

  /**
   * Tests with a user with correct permissions.
   *
   * User is created and switched to in parent::setUp.
   *
   * @return void
   *   Void.
   */
  public function testUserWithAccess() {

    $request = $this->createRequest('/accommodation/inventory?city=', 'GET');
    $response = $this->processRequest($request);

    $this->assertEquals($response->getStatusCode(), 200);
  }

}
