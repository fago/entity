<?php

/**
 * @file
 * Contains \Drupal\entity\Access\EntityRevisionAccessControlHandler.
 */

namespace Drupal\entity\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class EntityRevisionAccessControlHandler extends EntityAccessControlHandler {

  use EntityRevisionAccessControlHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);

    return $access->andIf($this->checkRevisionAccess($entity, $operation, $account));
  }


}
