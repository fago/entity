<?php

namespace Drupal\Tests\entity\Kernel\QueryAccess;

use Drupal\entity\QueryAccess\Condition;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessHandler;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the query access handler.
 *
 * @coversDefaultClass \Drupal\entity\QueryAccess\QueryAccessHandler
 * @group entity
 */
class QueryAccessHandlerTest extends EntityKernelTestBase {

  /**
   * The query access handler.
   *
   * @var \Drupal\entity\QueryAccess\QueryAccessHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity',
    'entity_query_access_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_query_access_test');

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type = $entity_type_manager->getDefinition('entity_query_access_test');
    $this->handler = QueryAccessHandler::createInstance($this->container, $entity_type);
  }

  /**
   * @covers ::getConditions
   */
  public function testView() {
    // User with no permissions.
    $user = $this->createUser([], ['access content']);
    $conditions = $this->handler->getConditions('view', $user);
    $this->assertEquals(0, $conditions->count());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertTrue($conditions->isAlwaysFalse());

    // Admin permission.
    $user = $this->createUser([], ['administer entity_query_access_test']);
    $conditions = $this->handler->getConditions('view', $user);
    $this->assertEquals(0, $conditions->count());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    // View permission.
    $user = $this->createUser([], ["view entity_query_access_test"]);
    $conditions = $this->handler->getConditions('view', $user);
    $expected_conditions = [
      new Condition('status', '1'),
    ];
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    // View permission for the first bundle.
    $user = $this->createUser([], [
      "view first entity_query_access_test",
    ]);
    $conditions = $this->handler->getConditions('view', $user);
    $expected_conditions = [
      new Condition('type', ['first']),
      new Condition('status', '1'),
    ];
    $this->assertEquals('AND', $conditions->getConjunction());
    $this->assertEquals(2, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    // View own unpublished permission.
    $user = $this->createUser([], ["view own unpublished entity_query_access_test"]);
    $conditions = $this->handler->buildConditions('view', $user);
    $expected_conditions = [
      new Condition('user_id', $user->id()),
      new Condition('status', '0'),
    ];
    $this->assertEquals(2, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    // Both view and view own unpublished permissions.
    $user = $this->createUser([], [
      "view entity_query_access_test",
      "view own unpublished entity_query_access_test",
    ]);
    $conditions = $this->handler->buildConditions('view', $user);
    $expected_conditions = [
      new Condition('status', '1'),
      (new ConditionGroup('AND'))
        ->addCondition('user_id', $user->id())
        ->addCondition('status', '0')
        ->addCacheContexts(['user']),
    ];
    $this->assertEquals(2, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());
  }

  /**
   * @covers ::getConditions
   */
  public function testUpdateDelete() {
    foreach (['update', 'delete'] as $operation) {
      // User with no permissions.
      $user = $this->createUser([], ['access content']);
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
      $this->assertTrue($conditions->isAlwaysFalse());

      // Admin permission.
      $user = $this->createUser([], ['administer entity_query_access_test']);
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());

      // Any permission.
      $user = $this->createUser([], ["$operation any entity_query_access_test"]);
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());

      // Own permission.
      $user = $this->createUser([], ["$operation own entity_query_access_test"]);
      $conditions = $this->handler->getConditions($operation, $user);
      $expected_conditions = [
        new Condition('user_id', $user->id()),
      ];
      $this->assertEquals(1, $conditions->count());
      $this->assertEquals($expected_conditions, $conditions->getConditions());
      $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());

      // Any permission for the first bundle, own permission for the second.
      $user = $this->createUser([], [
        "$operation any first entity_query_access_test",
        "$operation own second entity_query_access_test",
      ]);
      $conditions = $this->handler->getConditions($operation, $user);
      $expected_conditions = [
        new Condition('type', ['first']),
        (new ConditionGroup('AND'))
          ->addCondition('user_id', $user->id())
          ->addCondition('type', ['second']),
      ];
      $this->assertEquals('OR', $conditions->getConjunction());
      $this->assertEquals(2, $conditions->count());
      $this->assertEquals($expected_conditions, $conditions->getConditions());
      $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());
    }
  }

}
