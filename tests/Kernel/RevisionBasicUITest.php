<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Kernel\RevisionBasicUITest.
 */

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_module_test\Entity\EntityWithRevisionRoutes;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group entity
 */
class RevisionBasicUITest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_module_test', 'system', 'user', 'entity'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test__rev_routes');
    $this->installSchema('system', 'router');

    \Drupal::service('router.builder')->rebuild();
  }

  public function testRevisionView() {
    $entity = EntityWithRevisionRoutes::create([]);
    $entity->save();

    $revision = clone $entity;
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->save();

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    $request = Request::create($revision->url('revision'));
    $response = $http_kernel->handle($request);
    $this->assertEquals(403, $response->getStatusCode());

    $role = Role::create(['id' => 'test_role']);
    $role->grantPermission('view all entity_test__rev_routes revisions');
    $role->grantPermission('administer entity_test__revision_routes');
    $role->save();

    $user = User::create([
      'name' => 'Test user',
    ]);
    $user->addRole($role->id());
    \Drupal::service('account_switcher')->switchTo($user);

    $request = Request::create($revision->url('revision'));
    $response = $http_kernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
  }

}
