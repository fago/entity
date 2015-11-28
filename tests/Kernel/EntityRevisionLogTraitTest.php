<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Kernel\EntityRevisionLogTraitTest.
 */

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_test\Entity\EntityWithRevisionLog;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\entity\Revision\EntityRevisionLogTrait
 */
class EntityRevisionLogTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity', 'entity_test', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
  }

  public function testEntityRevisionLog() {
    $user = User::create([
      'name' => 'user',
    ]);
    $user->save();
    $user2 = User::create([
      'name' => 'user2',
    ]);
    $user2->save();

    /** @var \Drupal\entity\Revision\EntityRevisionLogInterface $entity */
    $entity = EntityWithRevisionLog::create([
      'revision_user' => $user->id(),
      'revision_create' => 1447941735,
      'revision_log_message' => 'Test message',
    ]);

    $this->assertEquals(1447941735, $entity->getRevisionCreationTime());
    $this->assertEquals($user->id(), $entity->getRevisionUser()->id());
    $this->assertEquals('Test message', $entity->getRevisionLogMessage());

    $entity->setRevisionCreationTime(1234567890);
    $this->assertEquals(1234567890, $entity->getRevisionCreationTime());
    $entity->setRevisionUser($user2->id());
    $this->assertEquals($user2->id(), $entity->getRevisionUser()->id());
    $entity->setRevisionLogMessage('Giraffe!');
    $this->assertEquals('Giraffe!', $entity->getRevisionLogMessage());
  }

}
