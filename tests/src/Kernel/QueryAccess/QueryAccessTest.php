<?php

namespace Drupal\Tests\entity\Kernel\QueryAccess;

use Drupal\entity_module_test\Entity\EnhancedEntity;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Test query access filtering for EntityQuery and Views.
 *
 * @group entity
 *
 * @see \Drupal\entity\QueryAccess\QueryAccessHandler
 * @see \Drupal\entity\QueryAccess\EntityQueryAlter
 * @see \Drupal\entity\QueryAccess\ViewsQueryAlter
 */
class QueryAccessTest extends EntityKernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * The test entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $entities;

  /**
   * The entity_test_enhanced storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity',
    'entity_module_test',
    'user',
    'views',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_enhanced');
    $this->installConfig(['entity_module_test']);

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();

    $first_entity = EnhancedEntity::create([
      'type' => 'first',
      'label' => 'First',
      'status' => 1,
    ]);
    $first_entity->save();

    $first_entity->set('name', 'First!');
    $first_entity->set('status', 0);
    $first_entity->setNewRevision(TRUE);
    $first_entity->save();

    $second_entity = EnhancedEntity::create([
      'type' => 'first',
      'label' => 'Second',
      'status' => 0,
    ]);
    $second_entity->save();

    $second_entity->set('name', 'Second!');
    $second_entity->set('status', 1);
    $second_entity->setNewRevision(TRUE);
    $second_entity->save();

    $third_entity = EnhancedEntity::create([
      'type' => 'second',
      'label' => 'Third',
      'status' => 1,
    ]);
    $third_entity->save();

    $third_entity->set('name', 'Third!');
    $third_entity->setNewRevision(TRUE);
    $third_entity->save();

    $this->entities = [$first_entity, $second_entity, $third_entity];
    $this->storage = \Drupal::entityTypeManager()->getStorage('entity_test_enhanced');
  }

  /**
   * Tests EntityQuery filtering.
   */
  public function testEntityQuery() {
    // Admin permission, full access.
    $admin_user = $this->createUser([], ['administer entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($admin_user);

    $result = $this->storage->getQuery()->sort('id')->execute();
    $this->assertEquals([
      $this->entities[0]->id(),
      $this->entities[1]->id(),
      $this->entities[2]->id(),
    ], array_values($result));

    // No view permissions, no access.
    $user = $this->createUser([], ['access content']);
    \Drupal::currentUser()->setAccount($user);

    $result = $this->storage->getQuery()->execute();
    $this->assertEmpty($result);

    // View (published-only).
    $user = $this->createUser([], ['view entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $result = $this->storage->getQuery()->sort('id')->execute();
    $this->assertEquals([
      $this->entities[1]->id(),
      $this->entities[2]->id(),
    ], array_values($result));

    // View $bundle (published-only).
    $user = $this->createUser([], ['view first entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $result = $this->storage->getQuery()->sort('id')->execute();
    $this->assertEquals([
      $this->entities[1]->id(),
    ], array_values($result));
  }

  /**
   * Tests EntityQuery filtering when all revisions are queried.
   */
  public function testEntityQueryWithRevisions() {
    // Admin permission, full access.
    $admin_user = $this->createUser([], ['administer entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($admin_user);

    $result = $this->storage->getQuery()->allRevisions()->sort('id')->execute();
    $this->assertEquals([
      '1' => $this->entities[0]->id(),
      '2' => $this->entities[0]->id(),
      '3' => $this->entities[1]->id(),
      '4' => $this->entities[1]->id(),
      '5' => $this->entities[2]->id(),
      '6' => $this->entities[2]->id(),
    ], $result);

    // No view permissions, no access.
    $user = $this->createUser([], ['access content']);
    \Drupal::currentUser()->setAccount($user);

    $result = $this->storage->getQuery()->execute();
    $this->assertEmpty($result);

    // View (published-only).
    $user = $this->createUser([], ['view entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $result = $this->storage->getQuery()->allRevisions()->sort('id')->execute();
    $this->assertEquals([
      '1' => $this->entities[0]->id(),
      '4' => $this->entities[1]->id(),
      '5' => $this->entities[2]->id(),
      '6' => $this->entities[2]->id(),
    ], $result);

    // View $bundle (published-only).
    $user = $this->createUser([], ['view first entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $result = $this->storage->getQuery()->allRevisions()->sort('id')->execute();
    $this->assertEquals([
      '1' => $this->entities[0]->id(),
      '4' => $this->entities[1]->id(),
    ], $result);
  }

  /**
   * Tests Views filtering.
   */
  public function testViews() {
    // Admin permission, full access.
    $admin_user = $this->createUser([], ['administer entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($admin_user);

    $view = Views::getView('entity_test_enhanced');
    $view->execute();
    $this->assertIdenticalResultset($view, [
      ['id' => $this->entities[0]->id()],
      ['id' => $this->entities[1]->id()],
      ['id' => $this->entities[2]->id()],
    ], ['id' => 'id']);

    // No view permissions, no access.
    $user = $this->createUser([], ['access content']);
    \Drupal::currentUser()->setAccount($user);

    $view = Views::getView('entity_test_enhanced');
    $view->execute();
    $this->assertIdenticalResultset($view, []);

    // View (published-only).
    $user = $this->createUser([], ['view entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $view = Views::getView('entity_test_enhanced');
    $view->execute();
    $this->assertIdenticalResultset($view, [
      ['id' => $this->entities[1]->id()],
      ['id' => $this->entities[2]->id()],
    ], ['id' => 'id']);

    // View $bundle (published-only).
    $user = $this->createUser([], ['view first entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $view = Views::getView('entity_test_enhanced');
    $view->execute();
    $this->assertIdenticalResultset($view, [
      ['id' => $this->entities[1]->id()],
    ], ['id' => 'id']);
  }

  /**
   * Tests Views filtering when all revisions are queried.
   */
  public function testViewsWithRevisions() {
    // Admin permission, full access.
    $admin_user = $this->createUser([], ['administer entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($admin_user);

    $view = Views::getView('entity_test_enhanced_revisions');
    $view->execute();
    $this->assertIdenticalResultset($view, [
      ['vid' => '1', 'id' => $this->entities[0]->id()],
      ['vid' => '2', 'id' => $this->entities[0]->id()],
      ['vid' => '3', 'id' => $this->entities[1]->id()],
      ['vid' => '4', 'id' => $this->entities[1]->id()],
      ['vid' => '5', 'id' => $this->entities[2]->id()],
      ['vid' => '6', 'id' => $this->entities[2]->id()],
    ], ['vid' => 'vid']);

    // No view permissions, no access.
    $user = $this->createUser([], ['access content']);
    \Drupal::currentUser()->setAccount($user);

    $view = Views::getView('entity_test_enhanced');
    $view->execute();
    $this->assertIdenticalResultset($view, []);

    // View (published-only).
    $user = $this->createUser([], ['view entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $view = Views::getView('entity_test_enhanced_revisions');
    $view->execute();
    $this->assertIdenticalResultset($view, [
      ['vid' => '1', 'id' => $this->entities[0]->id()],
      ['vid' => '4', 'id' => $this->entities[1]->id()],
      ['vid' => '5', 'id' => $this->entities[2]->id()],
      ['vid' => '6', 'id' => $this->entities[2]->id()],
    ], ['vid' => 'vid']);

    // View $bundle (published-only).
    $user = $this->createUser([], ['view first entity_test_enhanced']);
    \Drupal::currentUser()->setAccount($user);

    $view = Views::getView('entity_test_enhanced_revisions');
    $view->execute();
    $this->assertIdenticalResultset($view, [
      ['vid' => '1', 'id' => $this->entities[0]->id()],
      ['vid' => '4', 'id' => $this->entities[1]->id()],
    ], ['vid' => 'vid']);
  }

}
