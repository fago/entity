<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_query_access_test\Entity\EntityQueryAccessTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Test the cacheable query_access.
 *
 * @group entity
 *
 * @see \Drupal\entity\Query\QueryAccessHandler
 * @see \Drupal\entity\Query\SqlQueryAlter
 */
class QueryAccessTest extends KernelTestBase {

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

  
  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   * @param array $permissions
   *   (optional) Array of permission names to assign to user.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser($values = [], $permissions = []) {
    if ($permissions) {
      // Create a new role and apply permissions to it.
      $role = Role::create([
        'id' => strtolower($this->randomMachineName(8)),
        'label' => $this->randomMachineName(8),
      ]);
      $role->save();
      user_role_grant_permissions($role->id(), $permissions);
      $values['roles'][] = $role->id();
    }

    $account = User::create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

  public function testAccess() {
    $other_user = $this->createUser();
    $admin_user = $this->createUser([], ['administer entity_query_access_test']);
    $user_view = $this->createUser([], ['view entity_query_access_test']);
    $user_view_own_up = $this->createUser([], ['view entity_query_access_test', 'view own unpublished entity_query_access_test']);

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
