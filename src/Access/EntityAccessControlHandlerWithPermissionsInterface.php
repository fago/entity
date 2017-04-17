<?php

/**
 * @file
 * Contains \Drupal\entity\Access\EntityAccessControlHandlerWithPermissionsInterface.
 */
namespace Drupal\entity\Access;

use Drupal\Core\Entity\EntityAccessControlHandlerInterface;

/**
 * Provides additional access control behavior.
 */
interface EntityAccessControlHandlerWithPermissionsInterface extends EntityAccessControlHandlerInterface {

  /**
   * Builds a list of permissions for the current entity type.
   *
   * @return array
   *   The permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function buildPermissions();

}
