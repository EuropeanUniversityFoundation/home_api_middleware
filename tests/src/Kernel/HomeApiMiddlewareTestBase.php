<?php

namespace Drupal\Tests\home_api_middleware\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
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

    $default_settings = Settings::getAll();
    include_once 'settings.mocked.php';
    $settings = array_merge($default_settings, $home_settings);
    new Settings($settings);

    $this->installEntitySchema('user');

    // Create and save a role with permission to call endpoints.
    $role_with_access = Role::create(
      [
        'label' => 'API user',
        'id' => 'api_user',
        'permissions' => [
          'use home api middleware',
        ],
      ]
    );

    $role_with_access->save();

    // Create user having the role with permissions to call endpoints.
    $userWithAccess = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$role_with_access->id()],
    ]);
    $userWithAccess->save();

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo($userWithAccess);
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
