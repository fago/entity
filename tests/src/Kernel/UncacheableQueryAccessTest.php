<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_module_test\Entity\EnhancedEntityWithOwner;
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

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_enhanced_with_owner');
  }

  public function testAccess() {
    $other_user = $this->createUser();
    $admin_user = $this->createUser(['administer entity_test_enhanced_with_owner']);
    $user_view_any = $this->createUser(['view any entity_test_enhanced_with_owner']);
    $user_view_own = $this->createUser(['view own entity_test_enhanced_with_owner']);
    $user_view_bundle_any = $this->createUser(['view any first entity_test_enhanced_with_owner']);
    $user_view_bundle_own = $this->createUser(['view own first entity_test_enhanced_with_owner']);

    $first_entity_other = EnhancedEntityWithOwner::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $other_user->id(),
    ]);
    $first_entity_other->save();

    $first_entity_own = EnhancedEntityWithOwner::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $user_view_bundle_own->id(),
    ]);
    $first_entity_own->save();

    $first_entity_3 = EnhancedEntityWithOwner::create([
      'type' => 'first',
      'label' => 'First',
      'user_id' => $user_view_own->id(),
    ]);
    $first_entity_3->save();

    $second_entity_other = EnhancedEntityWithOwner::create([
      'type' => 'second',
      'label' => 'Second',
      'user_id' => $other_user->id(),
    ]);
    $second_entity_other->save();

    $second_entity_own = EnhancedEntityWithOwner::create([
      'type' => 'second',
      'label' => 'Second',
      'user_id' => $user_view_own->id(),
    ]);
    $second_entity_own->save();

    $entityTypeManager = \Drupal::entityTypeManager();

    $query = $entityTypeManager->getStorage('entity_test_enhanced_with_owner')->getQuery();
    \Drupal::currentUser()->setAccount($admin_user);
    $result = $query->execute();
    sort($result);
    $this->assertEquals([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id(), $second_entity_other->id(), $second_entity_own->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_test_enhanced_with_owner')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_any);
    $result = $query->execute();
    sort($result);
    $this->assertEquals([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id(), $second_entity_other->id(), $second_entity_own->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_test_enhanced_with_owner')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_own);
    $result = $query->execute();
    $this->assertEquals([$first_entity_3->id(), $second_entity_own->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_test_enhanced_with_owner')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_bundle_any);
    $result = $query->execute();
    $this->assertEquals([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id()], array_values($result));

    $query = $entityTypeManager->getStorage('entity_test_enhanced_with_owner')->getQuery();
    \Drupal::currentUser()->setAccount($user_view_bundle_own);
    $result = $query->execute();
    $this->assertEquals([$first_entity_own->id()], array_values($result));

    $column_map = [
      'id' => 'id',
    ];

    \Drupal::currentUser()->setAccount($user_view_any);
    $view = Views::getView('entity_test_enhanced_with_owner');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id(), $second_entity_other->id(), $second_entity_own->id()]), $column_map);

    \Drupal::currentUser()->setAccount($user_view_own);
    $view = Views::getView('entity_test_enhanced_with_owner');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_3->id(), $second_entity_own->id()]), $column_map);

    \Drupal::currentUser()->setAccount($user_view_bundle_any);
    $view = Views::getView('entity_test_enhanced_with_owner');
    $view->execute();
    $this->assertIdenticalResultset($view, $this->convertToExpectedResult([$first_entity_other->id(), $first_entity_own->id(), $first_entity_3->id()]), $column_map);

    \Drupal::currentUser()->setAccount($user_view_bundle_own);
    $view = Views::getView('entity_test_enhanced_with_owner');
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
