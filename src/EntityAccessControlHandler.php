<?php

namespace Drupal\entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * @deprecated
 */
class EntityAccessControlHandler extends PermissionBasedEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
    $this->requiresViewOwnAccessCheck = FALSE;
  }

}
