<?php

namespace Drupal\entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * @internal
 */
class PermissionBasedEntityAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * If access checks should permit viewing own unpublished entities.
   *
   * This has severe caching implications because access must always vary per
   * user. It is defined by the `requires_view_own_access_check` entity type
   * annotation.
   *
   * @var bool
   */
  protected $requiresViewOwnAccessCheck;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
    $this->requiresViewOwnAccessCheck = (bool) $this->entityType->get('requires_view_own_access_check');
    if (!$entity_type->hasHandlerClass('permission_provider') || !is_a($entity_type->getHandlerClass('permission_provider'), EntityPermissionProviderBase::class, TRUE)) {
      throw new \Exception('\Drupal\entity\EntityAccessControlHandler requires the \Drupal\entity\EntityPermissionProvider or \Drupal\entity\UncacheableEntityPermissionProvider permission provider.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      if ($entity instanceof EntityOwnerInterface && $this->requiresViewOwnAccessCheck) {
        $result = $this->checkEntityOwnerPermissions($entity, $operation, $account);
      }
      else {
        $result = $this->checkEntityPermissions($entity, $operation, $account);
      }
    }

    return $result;
  }

  /**
   * Checks the entity operation and bundle permissions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    $permissions = [
      "$operation {$entity->getEntityTypeId()}",
      "$operation {$entity->bundle()} {$entity->getEntityTypeId()}",
    ];

    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

  /**
   * Checks the entity operation and bundle permissions, with owners.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityOwnerPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\user\EntityOwnerInterface $entity */
    $any_permissions = [
      "$operation any {$entity->getEntityTypeId()}",
      "$operation any {$entity->bundle()} {$entity->getEntityTypeId()}",
    ];
    if ($operation === 'view' && $entity instanceof EntityPublishedInterface) {
      $own_permissions = [
        "view own unpublished {$entity->getEntityTypeId()}",
        "view own unpublished {$entity->bundle()} {$entity->getEntityTypeId()}",
      ];
    }
    else {
      $own_permissions = [
        "$operation own {$entity->getEntityTypeId()}",
        "$operation own {$entity->bundle()} {$entity->getEntityTypeId()}",
      ];
    }

    $result = AccessResult::allowedIfHasPermissions($account, $any_permissions, 'OR');

    if ($entity instanceof EntityPublishedInterface) {
      // The result must be reevaluated if the published status changes.
      $result = $result->andIf(AccessResult::allowedIf($entity->isPublished())->addCacheableDependency($entity));
    }

    // If the result was not allowed, then check if the entity is owned by the
    // account.
    if (!$result->isAllowed()) {
      if ($account->id() == $entity->getOwnerId()) {
        // The access result must be reevaluated the entity's owner or published
        // state is updated.
        $result = $result->orIf(AccessResult::allowedIfHasPermissions($account, $own_permissions, 'OR')->addCacheableDependency($entity));
      }
      // The result must be reevaluated if the account is different.
      $result = $result->cachePerUser();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isNeutral()) {
      $permissions = [
        'administer ' . $this->entityTypeId,
        'create ' . $this->entityTypeId,
      ];
      if ($entity_bundle) {
        $permissions[] = 'create ' . $entity_bundle . ' ' . $this->entityTypeId;
      }

      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }

    return $result;
  }

}
