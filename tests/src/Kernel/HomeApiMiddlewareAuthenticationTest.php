<?php

namespace Drupal\Tests\home_api_middleware\Kernel;

use Drupal\Tests\home_api_middleware\Kernel\HomeApiMiddlewareTestBase;

/**
 * Test description.
 *
 * @group home_api_middleware
 */
class HomeApiMiddlewareAuthenticationTest extends HomeApiMiddlewareTestBase {

  /**
   * Tests if token authentication is successful.
   *
   * @return void
   *   Void.
   */
  public function testGetToken() {
    $authenticationManager = \Drupal::service('home_api_middleware.authentication_manager');
    $tokenResponse = $authenticationManager->getToken();

    $this->assertArrayHasKey('token', $tokenResponse);
    $this->assertArrayHasKey('expire', $tokenResponse);
  }

}
