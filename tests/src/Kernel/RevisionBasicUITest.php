<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Kernel\RevisionBasicUITest.
 */

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_module_test\Entity\EnhancedEntity;
use Drupal\entity_module_test\Entity\EnhancedEntityBundle;
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
    $this->installEntitySchema('entity_test_enhanced');
    $this->installSchema('system', 'router');

    $bundle = EnhancedEntityBundle::create([
      'id' => 'default',
      'label' => 'Default',
    ]);
    $bundle->save();

    \Drupal::service('router.builder')->rebuild();
  }

  public function testRevisionView() {
    $entity = EnhancedEntity::create([
      'name' => 'rev 1',
      'type' => 'default',
    ]);
    $entity->save();

    $revision = clone $entity;
    $revision->name->value = 'rev 2';
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->save();

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    $request = Request::create($revision->url('revision'));
    $response = $http_kernel->handle($request);
    $this->assertEquals(403, $response->getStatusCode());

    $role = Role::create(['id' => 'test_role']);
    $role->grantPermission('view all entity_test_enhanced revisions');
    $role->grantPermission('administer entity_test_enhanced');
    $role->save();

    $user = User::create([
      'name' => 'Test user',
    ]);
    $user->addRole($role->id());
    \Drupal::service('account_switcher')->switchTo($user);

    $request = Request::create($revision->url('revision'));
    $response = $http_kernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertNotContains('rev 1', $response->getContent());
    $this->assertContains('rev 2', $response->getContent());
  }

}
