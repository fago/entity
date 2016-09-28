<?php

namespace Drupal\entity_module_test;

use Drupal\entity\EntityPermissions;

/**
 * Permissions implementation for entity_test_enhanced.
 */
class EntityEnhancedOwnerPermissions extends EntityPermissions {

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId() {
    return 'entity_test_owner';
  }

}
