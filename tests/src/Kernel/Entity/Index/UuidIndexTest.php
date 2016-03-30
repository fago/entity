<?php

namespace Drupal\Tests\entity\Kernal\Entity\Index;

use Drupal\entity_module_test\Entity\EnhancedEntity;
use Drupal\entity_module_test\Entity\EnhancedEntityBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\entity\Entity\Index\UuidIndex
 * @group entity
 */
class UuidIndexTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity', 'entity_module_test', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_enhanced');
    $this->installSchema('system', 'sequences');

    $bundle = EnhancedEntityBundle::create([
      'id' => 'default',
      'label' => 'Default',
    ]);
    $bundle->save();
  }

  public function testUuidIndex() {
    $user = User::create([
      'name' => 'user',
    ]);
    $user->save();

    $entity = EnhancedEntity::create([
      'type' => 'default',
      'revision_user' => $user->id(),
      'revision_created' => 1447941735,
      'revision_log_message' => 'Test message',
    ]);
    $entity->save();

    $index = \Drupal::service('entity.index.uuid')->get($entity->uuid());
    $this->assertEquals($entity->getEntityTypeId(), $index['entity_type_id'], 'Entity type id found.');
    $this->assertEquals($entity->id(), $index['entity_id'], 'Entity id found.');
  }

}
