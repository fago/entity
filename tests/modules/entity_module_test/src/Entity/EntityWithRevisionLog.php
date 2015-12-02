<?php

/**
 * @file
 * Contains \Drupal\entity_module_test\Entity\EntityWithRevisionLog.
 */

namespace Drupal\entity_module_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity\EntityKeysFieldsTrait;
use Drupal\entity\Revision\EntityRevisionLogTrait;

/**
 * @ContentEntityType(
 *   id = "entity_test__revision_log",
 *   label = @Translation("Entity test with revision log"),
 *   handlers = {
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *   },
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class EntityWithRevisionLog extends ContentEntityBase {

  use EntityRevisionLogTrait;
  use EntityKeysFieldsTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields += static::entityKeysBaseFieldDefinitions($entity_type);
    $fields += static::entityRevisionLogBaseFieldDefinitions();

    return $fields;
  }

}
