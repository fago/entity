<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_query_access_test\Entity\EntityQueryAccessTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Tests query_access handlers.
 *
 * @group entity
 *
 * @see \Drupal\entity\Query\QueryAccessHandler
 * @see \Drupal\entity\Query\SqlQueryAlter
 */
class QueryAccessTest extends KernelTestBase {

  use UserCreationTrait;
  use ViewResultAssertionTrait;

  public static $modules = ['entity', 'system', 'entity_test', 'entity_query_access_test', 'user', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_query_access_test');
    $this->installConfig('entity_query_access_test');
  }

  public function testAccess() {
    $other_user = $this->createUser();
    $admin_user = $this->createUser(['administer entity_query_access_test']);
    $user_view = $this->createUser(['view entity_query_access_test']);
    $user_view_own_up = $this->createUser(['view entity_query_access_test', 'view own unpublished entity_query_access_test']);

    $first_entity_other = EntityQueryAccessTest::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $other_user->id(),
      'status' => 1,
    ]);
    $first_entity_other->save();

    $first_entity_own_up = EntityQueryAccessTest::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $user_view_own_up->id(),
      'status' => 0,
    ]);
    $first_entity_own_up->save();

    $second_entity_other = EntityQueryAccessTest::create([
      'type' => 'second',
      'label' => 'Second',
      'user_id' => $other_user->id(),
      'status' => 1,
    ]);
    $second_entity_other->save();

    $entityTypeManager = \Drupal::entityTypeManager();

    $query = $entityTypeManager->getStorage('entity_query_access_test')->getQuery();
    \Drupal::currentUser()->setAccount($admin_user);
    $result = $query->execute();
    sort($result);
    $this->assertEquals([$first_entity_other->id(), $first_entity_own_up->id(), $second_entity_other->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_query_access_test')->getQuery();
    \Drupal::currentUser()->setAccount($user_view);
    $result = $query->execute();
    sort($result);
    $this->assertEquals([$first_entity_other->id(),  $second_entity_other->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_query_access_test')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_own_up);
    $result = $query->execute();
    $this->assertEquals([$first_entity_other->id(), $first_entity_own_up->id(), $second_entity_other->id()], array_values($result));

    $column_map = [
      'id' => 'id',
    ];

    \Drupal::currentUser()->setAccount($user_view);
    $view = Views::getView('entity_test_query_access');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_other->id(),  $second_entity_other->id()]), $column_map);

    \Drupal::currentUser()->setAccount($user_view_own_up);
    $view = Views::getView('entity_test_query_access');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_other->id(), $first_entity_own_up->id(), $second_entity_other->id()]), $column_map);
  }

  protected function convertToExpectedResult($entity_ids) {
    return array_map(function ($entity_id) {
      return [
        'id' => $entity_id,
      ];
    }, $entity_ids);
  }

}
