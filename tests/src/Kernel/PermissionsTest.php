<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_module_test\Entity\EnhancedEntity;
use Drupal\entity_module_test\Entity\EnhancedEntityBundle;
use Drupal\entity_module_test\Entity\EnhancedOwnerEntity;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the permissions builder and generic entity access control handler.
 *
 * @group entity
 */
class PermissionsTest extends EntityKernelTestBase {

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
    $this->installEntitySchema('entity_test_owner');
    $this->installSchema('system', 'router');
    $this->installConfig(['system']);

    $bundle = EnhancedEntityBundle::create([
      'id' => 'default',
      'label' => 'Default',
    ]);
    $bundle->save();
    $bundle = EnhancedEntityBundle::create([
      'id' => 'tester',
      'label' => 'Tester',
    ]);
    $bundle->save();

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests the generated permissions.
   */
  public function testGeneratedPermissions() {
    $permissions = $this->container->get('user.permissions')->getPermissions();

    $this->assertTrue(isset($permissions['administer entity_test_enhanced']));
    $this->assertTrue(isset($permissions['access entity_test_enhanced overview']));
    $this->assertTrue(isset($permissions['create default entity_test_enhanced']));
    $this->assertTrue(isset($permissions['create tester entity_test_enhanced']));
    $this->assertFalse(isset($permissions['create own tester entity_test_enhanced']));
  }

  /**
   * Tests the access controller.
   */
  public function testAccessControlHandler() {
    // Offset uid = 1.
    $this->createUser();

    $entity = EnhancedEntity::create([
      'name' => 'Llama',
      'type' => 'default',
    ]);
    $entity->save();

    $user1 = $this->createUser([], ['bypass entity_test_enhanced access']);
    $user2 = $this->createUser([], ['create default entity_test_enhanced', 'update default entity_test_enhanced']);
    $user3 = $this->createUser([], ['create tester entity_test_enhanced', 'update tester entity_test_enhanced']);

    $this->assertTrue($entity->access('create', $user1));
    $this->assertTrue($entity->access('create', $user2));
    $this->assertFalse($entity->access('create', $user3));
    $this->assertTrue($entity->access('create', $user1));
    $this->assertTrue($entity->access('create', $user2));
    $this->assertFalse($entity->access('create', $user3));
    $this->assertTrue($entity->access('update', $user1));
    $this->assertTrue($entity->access('update', $user2));
    $this->assertFalse($entity->access('update', $user3));

    $user4 = $this->createUser([], ['update own default entity_test_owner']);
    $user5 = $this->createUser([], ['update any default entity_test_owner']);
    $user6 = $this->createUser([], ['bypass entity_test_owner access']);

    $entity = EnhancedOwnerEntity::create([
      'name' => 'Alpaca',
      'type' => 'default',
      'uid' => $user4->id(),
    ]);
    $entity->save();
    $other_entity = EnhancedOwnerEntity::create([
      'name' => 'Emu',
      'type' => 'default',
      'uid' => $user5->id(),
    ]);
    $other_entity->save();

    // Owner can update entity.
    $this->assertTrue($entity->access('update', $user4));

    // User cannot update entities they do not own.
    $this->assertFalse($other_entity->access('update', $user4));

    // User with "any" can update entities they do not own.
    $this->assertTrue($entity->access('update', $user5));

    // User with "any" can update their own entries.
    $this->assertTrue($other_entity->access('update', $user5));

    // User with bypass can update both entities.
    $this->assertTrue($entity->access('update', $user6));
    $this->assertTrue($other_entity->access('update', $user6));
  }

}
