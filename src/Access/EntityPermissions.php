<?php

/**
 * @file
 * Contains \Drupal\entity\AccessEntityPermissions.
 */

namespace Drupal\entity\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides dynamic permissions for participating entity types.
 */
class EntityPermissions {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new EntityPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * Builds a list of permissions for the participating entity types.
   *
   * @return array
   *   The permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function buildPermissions() {
    $permissions = [];
    foreach ($this->getParticipatingEntityTypes() as $entity_type) {
      $access_handler_class = $entity_type->getHandlerClass('access');
      /** @var \Drupal\entity\Access\EntityAccessControlHandlerWithPermissionsInterface $access_handler */
      $access_handler = new $access_handler_class($entity_type);
      $permissions += $access_handler->buildPermissions();
    }

    return $permissions;
  }

  /**
   * Gets a list of participating entity types.
   *
   * This list includes all entity types that declare access handlers
   * implementing this module's
   * \Drupal\entity\Access\EntityAccessControlHandlerWithPermissionsInterface.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   The participating entity types.
   */
  protected function getParticipatingEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, function ($entity_type) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $access_handler_class = $entity_type->getHandlerClass('access');
      return is_subclass_of($access_handler_class, EntityAccessControlHandlerWithPermissionsInterface::class);
    });

    return $entity_types;
  }

}
