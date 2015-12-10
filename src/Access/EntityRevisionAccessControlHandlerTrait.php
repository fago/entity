<?php

/**
 * @file
 * Contains \Drupal\entity\Access\EntityRevisionAccessControlHandlerTrait.
 */

namespace Drupal\entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Trait usable by access control handler for check revision access check.
 */
trait EntityRevisionAccessControlHandlerTrait {

  /**
   * Gets the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected function getEntityTypeManager() {
    return \Drupal::entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  abstract public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE);

  /**
   * Performs access checks.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'update' or 'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkRevisionAccess(ContentEntityInterface $entity, $operation = 'view revision', AccountInterface $account) {
    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity->getEntityTypeId();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = $this->getEntityTypeManager()->getStorage($entity_type_id);

    $map = [
      'view_revision' => "view all $entity_type_id revisions",
      'update_revision' => "revert all $entity_type_id revisions",
      'delete_revision' => "delete all $entity_type_id revisions",
    ];
    $bundle = $entity->bundle();
    $type_map = [
      'view_revision' => "view $entity_type_id $bundle revisions",
      'update_revision' => "revert $entity_type_id $bundle revisions",
      'delete_revision' => "delete $entity_type_id $bundle revisions",
    ];

    if (!$entity || !isset($map[$operation]) || !isset($type_map[$operation])) {
      // If there was no node to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return AccessResult::forbidden();
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $entity->language()->getId();
    $cid = $entity->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $operation;

    if (!isset($this->accessCache[$cid])) {
      $admin_permission = $entity_type->getAdminPermission();
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$operation]) && !$account->hasPermission($type_map[$operation]) && !$account->hasPermission($admin_permission)) {
        return $this->accessCache[$cid] = AccessResult::forbidden();
      }

      if ($account->hasPermission($admin_permission)) {
        return $this->accessCache[$cid] = AccessResult::allowed();
      }
      else {
        // First check the access to the default revision and finally, if the
        // node passed in is not the default revision then access to that, too.
        return $this->accessCache[$cid] = $this->access($entity_storage->load($entity->id()), $operation, $account) && ($entity->isDefaultRevision() || $this->access($entity, $operation, $account));
      }
    }

    return $this->accessCache[$cid];
  }

}
