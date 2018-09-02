<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_query_access_test\Entity\EntityQueryAccessTest;
use Drupal\entity_query_access_test\Entity\EntityUncacheableQueryAccessTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Tests the uncacheable query_access handler.
 *
 * @group entity
 *
 * @see \Drupal\entity\QueryAccess\UncacheableQueryAccessHandler
 * @see \Drupal\entity\UncacheableQueryAccessHandler
 * @see \Drupal\entity\QueryAccess\SqlQueryAlter
 */
class UncacheableQueryAccessTest extends KernelTestBase {

  use UserCreationTrait;
  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity', 'system', 'entity_test', 'entity_query_access_test', 'user', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_uncacheable_query_access');
    $this->installConfig('entity_query_access_test');
  }

  public function testAccess() {
    $other_user = $this->createUser();
    $admin_user = $this->createUser(['administer entity_uncacheable_query_access']);
    $user_view_any = $this->createUser(['view any entity_uncacheable_query_access']);
    $user_view_own = $this->createUser(['view own entity_uncacheable_query_access']);
    $user_view_bundle_any = $this->createUser(['view any first entity_uncacheable_query_access']);
    $user_view_bundle_own = $this->createUser(['view own first entity_uncacheable_query_access']);

    $first_entity_other = EntityUncacheableQueryAccessTest::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $other_user->id(),
    ]);
    $first_entity_other->save();

    $first_entity_own = EntityUncacheableQueryAccessTest::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $user_view_bundle_own->id(),
    ]);
    $first_entity_own->save();

    $first_entity_3 = EntityUncacheableQueryAccessTest::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $user_view_own->id(),
    ]);
    $first_entity_3->save();

    $second_entity_other = EntityUncacheableQueryAccessTest::create([
      'type' => 'second',
      'label' => 'Second',
      'user_id' => $other_user->id(),
    ]);
    $second_entity_other->save();

    $second_entity_own = EntityUncacheableQueryAccessTest::create([
      'type' => 'second',
      'label' => 'Second',
      'user_id' => $user_view_own->id(),
    ]);
    $second_entity_own->save();

    $entityTypeManager = \Drupal::entityTypeManager();

    $query = $entityTypeManager->getStorage('entity_uncacheable_query_access')->getQuery();
    \Drupal::currentUser()->setAccount($admin_user);
    $result = $query->execute();
    sort($result);
    $this->assertEquals([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id(), $second_entity_other->id(), $second_entity_own->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_uncacheable_query_access')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_any);
    $result = $query->execute();
    sort($result);
    $this->assertEquals([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id(), $second_entity_other->id(), $second_entity_own->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_uncacheable_query_access')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_own);
    $result = $query->execute();
    $this->assertEquals([$first_entity_3->id(), $second_entity_own->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_uncacheable_query_access')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_bundle_any);
    $result = $query->execute();
    $this->assertEquals([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_uncacheable_query_access')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_bundle_own);
    $result = $query->execute();
    $this->assertEquals([$first_entity_own->id()], array_values($result));

    $column_map = [
      'id' => 'id',
    ];

    \Drupal::currentUser()->setAccount($user_view_any);
    $view = Views::getView('entity_test_uncacheable_query_access');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id(), $second_entity_other->id(), $second_entity_own->id()]), $column_map);

    \Drupal::currentUser()->setAccount($user_view_own);
    $view = Views::getView('entity_test_uncacheable_query_access');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_3->id(), $second_entity_own->id()]), $column_map);

    \Drupal::currentUser()->setAccount($user_view_bundle_any);
    $view = Views::getView('entity_test_uncacheable_query_access');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id()]), $column_map);

    \Drupal::currentUser()->setAccount($user_view_bundle_own);
    $view = Views::getView('entity_test_uncacheable_query_access');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_own->id()]), $column_map);
  }

  protected function convertToExpectedResult($entity_ids) {
    return array_map(function ($entity_id) {
      return [
        'id' => $entity_id,
      ];
    }, $entity_ids);
  }

}
