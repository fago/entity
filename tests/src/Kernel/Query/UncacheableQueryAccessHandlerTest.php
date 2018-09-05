<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity\Query\Condition;
use Drupal\entity\Query\ConditionGroup;
use Drupal\entity\Query\UncacheableQueryAccessHandler;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the query access handler.
 *
 * @coversDefaultClass \Drupal\entity\Query\UncacheableQueryAccessHandler
 * @group entity
 */
class UncacheableQueryAccessHandlerTest extends EntityKernelTestBase {

  /**
   * The query access handler.
   *
   * @var \Drupal\entity\Query\UncacheableQueryAccessHandler
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

    $this->installEntitySchema('entity_uncacheable_query_access');

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type = $entity_type_manager->getDefinition('entity_uncacheable_query_access');
    $this->handler = UncacheableQueryAccessHandler::createInstance($this->container, $entity_type);
  }

  /**
   * @covers ::buildConditions
   */
  public function testView() {
    // User with no permissions.
    $user = $this->createUser([], ['access content']);
    $conditions = $this->handler->buildConditions('view', $user);
    $expected_conditions = [
      new Condition('id', NULL, 'IS NULL'),
    ];
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());

    // Admin permission.
    $user = $this->createUser([], ['administer entity_uncacheable_query_access']);
    $conditions = $this->handler->buildConditions('view', $user);
    $this->assertEquals(0, $conditions->count());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());

    // Any permission.
    $user = $this->createUser([], ["view any entity_uncacheable_query_access"]);
    $conditions = $this->handler->buildConditions('view', $user);
    $expected_conditions = [
      new Condition('status', '1'),
    ];
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());

    // Own permission.
    $user = $this->createUser([], ["view own entity_uncacheable_query_access"]);
    $conditions = $this->handler->buildConditions('view', $user);
    $expected_conditions = [
      new Condition('user_id', $user->id()),
      new Condition('status', '1'),
    ];
    $this->assertEquals('AND', $conditions->getConjunction());
    $this->assertEquals(2, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());

    // Any permission for the first bundle, own permission for the second.
    $user = $this->createUser([], [
      "view any first entity_uncacheable_query_access",
      "view own second entity_uncacheable_query_access",
    ]);
    $conditions = $this->handler->buildConditions('view', $user);
    $expected_conditions = [
      (new ConditionGroup('OR'))
        ->addCacheContexts(['user', 'user.permissions'])
        ->addCondition('type', ['first'])
        ->addCondition((new ConditionGroup('AND'))
          ->addCondition('user_id', $user->id())
          ->addCondition('type', 'second')
        ),
      new Condition('status', '1'),
    ];
    $this->assertEquals('AND', $conditions->getConjunction());
    $this->assertEquals(2, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());
  }

  /**
   * @covers ::buildConditions
   */
  public function testUpdateDelete() {
    foreach (['update', 'delete'] as $operation) {
      // User with no permissions.
      $user = $this->createUser([], ['access content']);
      $conditions = $this->handler->buildConditions($operation, $user);
      $expected_conditions = [
        new Condition('id', NULL, 'IS NULL'),
      ];
      $this->assertEquals(1, $conditions->count());
      $this->assertEquals($expected_conditions, $conditions->getConditions());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());

      // Admin permission.
      $user = $this->createUser([], ['administer entity_uncacheable_query_access']);
      $conditions = $this->handler->buildConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());

      // Any permission.
      $user = $this->createUser([], ["$operation any entity_uncacheable_query_access"]);
      $conditions = $this->handler->buildConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());

      // Own permission.
      $user = $this->createUser([], ["$operation own entity_uncacheable_query_access"]);
      $conditions = $this->handler->buildConditions($operation, $user);
      $expected_conditions = [
        new Condition('user_id', $user->id()),
      ];
      $this->assertEquals(1, $conditions->count());
      $this->assertEquals($expected_conditions, $conditions->getConditions());
      $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());

      // Any permission for the first bundle, own permission for the second.
      $user = $this->createUser([], [
        "$operation any first entity_uncacheable_query_access",
        "$operation own second entity_uncacheable_query_access",
      ]);
      $conditions = $this->handler->buildConditions($operation, $user);
      $expected_conditions = [
        new Condition('type', ['first']),
        (new ConditionGroup('AND'))
          ->addCondition('user_id', $user->id())
          ->addCondition('type', 'second'),
      ];
      $this->assertEquals('OR', $conditions->getConjunction());
      $this->assertEquals(2, $conditions->count());
      $this->assertEquals($expected_conditions, $conditions->getConditions());
      $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());
    }
  }

}
