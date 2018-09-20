<?php

namespace Drupal\entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access based on the uncacheable entity permissions.
 *
 * @see \Drupal\entity\UncacheableEntityPermissionProvider
 *
 * Note: this access control handler will cause pages to be cached per user.
 */
class UncacheableEntityAccessControlHandler extends EntityAccessControlHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);

    if (!$entity_type->hasHandlerClass('permission_provider') || !is_a($entity_type->getHandlerClass('permission_provider'), UncacheableEntityPermissionProvider::class, TRUE)) {
      throw new \Exception('\Drupal\entity\UncacheableEntityAccessControlHandler requires the \Drupal\entity\UncacheableEntityPermissionProvider permission provider.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkEntityOwnerPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\user\EntityOwnerInterface $entity */
    if ($operation === 'view' && $entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
      if ($account->id() != $entity->getOwnerId()) {
        // There's no permission for viewing another user's unpublished entity.
        $result = AccessResult::neutral()->cachePerUser()->addCacheableDependency($entity);
      }
      else {
        $permissions = [
          "view own unpublished {$entity->getEntityTypeId()}",
        ];
        $result = AccessResult::allowedIfHasPermissions($account, $permissions)->cachePerUser();
      }
    }
    else {
      $result = parent::checkEntityOwnerPermissions($entity, $operation, $account);
    }

    // If the entity is unpublishable, the access result must be reevaluated
    // if its status changes.
    return $entity instanceof EntityPublishedInterface
      ? $result->addCacheableDependency($entity)
      : $result;
  }

}
