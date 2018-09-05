<?php

namespace Drupal\entity\Query;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides common logic for query access handlers.
 *
 * @see \Drupal\entity\Query\QueryAccessHandler
 * @see \Drupal\entity\Query\UncacheableQueryAccessHandler
 */
abstract class QueryAccessHandlerBase implements EntityHandlerInterface, QueryAccessHandlerInterface {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new UncacheableQueryAccessHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeBundleInfoInterface $bundle_info, AccountInterface $current_user) {
    $this->entityType = $entity_type;
    $this->bundleInfo = $bundle_info;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.bundle.info'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConditions($operation, AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $entity_type_id = $this->entityType->id();

    if ($account->hasPermission("administer {$entity_type_id}")) {
      // The user has full access to all operations, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    if ($this->entityType->entityClassImplements(EntityOwnerInterface::class)) {
      $entity_conditions = $this->buildEntityOwnerConditions($operation, $account);
    }
    else {
      $entity_conditions = $this->buildEntityConditions($operation, $account);
    }

    $conditions = NULL;
    if ($operation == 'view' && $this->entityType->entityClassImplements(EntityPublishedInterface::class)) {
      $published_key = $this->entityType->getKey('published');
      if ($entity_conditions) {
        // Restrict the existing conditions to published entities only.
        $conditions = new ConditionGroup('AND');
        $conditions->addCacheContexts(['user.permissions']);
        $conditions->addCondition($entity_conditions);
        $conditions->addCondition($published_key, '1');
      }
    }
    else {
      $conditions = $entity_conditions;
    }

    if (!$conditions) {
      // The user doesn't have access to any entities.
      // Falsify the query to ensure no results are returned.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      $conditions->addCondition($this->entityType->getKey('id'), NULL, 'IS NULL');
    }

    return $conditions;
  }

  /**
   * Builds the conditions for entities that have an owner.
   *
   * @param string $operation
   *   The access operation. One of "view", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to restrict access.
   *
   * @return \Drupal\entity\Query\ConditionGroup|null
   *   The conditions, or NULL if the user doesn't have access to any entity.
   */
  protected function buildEntityOwnerConditions($operation, AccountInterface $account) {
    $entity_type_id = $this->entityType->id();
    $uid_key = $this->entityType->getKey('uid');
    $bundle_key = $this->entityType->getKey('bundle');

    $conditions = new ConditionGroup('OR');
    $conditions->addCacheContexts(['user.permissions']);
    // Any $entity_type permission.
    if ($account->hasPermission("$operation any {$entity_type_id}")) {
      // The user has full access, no conditions needed.
      return $conditions;
    }

    $bundles = array_keys($this->bundleInfo->getBundleInfo($entity_type_id));
    $bundles_with_any_permission = [];
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("$operation any $bundle $entity_type_id")) {
        $bundles_with_any_permission[] = $bundle;
      }
    }
    // Any $bundle permission.
    if ($bundles_with_any_permission) {
      $conditions->addCondition($bundle_key, $bundles_with_any_permission);
    }

    // Own $entity_type permission.
    if ($account->hasPermission("$operation own $entity_type_id")) {
      $conditions->addCacheContexts(['user']);
      $conditions->addCondition($uid_key, $account->id());
    }

    // Own $bundle permission.
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("$operation own $bundle $entity_type_id")) {
        $conditions->addCacheContexts(['user']);
        $conditions->addCondition((new ConditionGroup('AND'))
          ->addCondition($uid_key, $account->id())
          ->addCondition($bundle_key, $bundle)
        );
      }
    }

    return $conditions->count() ? $conditions : NULL;
  }

  /**
   * Builds the conditions for entities that do not have an owner.
   *
   * @param string $operation
   *   The access operation. One of "view", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to restrict access.
   *
   * @return \Drupal\entity\Query\ConditionGroup|null
   *   The conditions, or NULL if the user doesn't have access to any entity.
   */
  protected function buildEntityConditions($operation, AccountInterface $account) {
    $entity_type_id = $this->entityType->id();
    $bundle_key = $this->entityType->getKey('bundle');

    $conditions = new ConditionGroup('OR');
    $conditions->addCacheContexts(['user.permissions']);
    // The $entity_type permission.
    if ($account->hasPermission("$operation {$entity_type_id}")) {
      // The user has full access, no conditions needed.
      return $conditions;
    }

    $bundles = array_keys($this->bundleInfo->getBundleInfo($entity_type_id));
    $bundles_with_any_permission = [];
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("$operation $bundle $entity_type_id")) {
        $bundles_with_any_permission[] = $bundle;
      }
    }
    // The $bundle permission.
    if ($bundles_with_any_permission) {
      $conditions->addCondition($bundle_key, $bundles_with_any_permission);
    }

    return $conditions->count() ? $conditions : NULL;
  }

}
