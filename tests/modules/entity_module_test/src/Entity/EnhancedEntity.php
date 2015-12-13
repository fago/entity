<?php

/**
 * @file
 * Contains \Drupal\entity_module_test\Entity\EnhancedEntity.
 */

namespace Drupal\entity_module_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity\EntityKeysFieldsTrait;
use Drupal\entity\Revision\EntityRevisionLogTrait;

/**
 * Provides a test entity which uses all the capabilities of entity module.
 *
 * @ContentEntityType(
 *   id = "entity_test_enhanced",
 *   label = @Translation("Entity test with enhancements"),
 *   handlers = {
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "route_provider" = {
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_enhanced",
 *   data_table = "entity_test_enhanced_field_data",
 *   revision_table = "entity_test_enhanced_revision",
 *   revision_data_table = "entity_test_enhanced_field_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   admin_permission = "administer entity_test_enhanced",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "revision" = "/entity_test_enhanced/{entity_test_enhanced}/revisions/{entity_test_enhanced_revision}/view",
 *   }
 * )
 */
class EnhancedEntity extends ContentEntityBase {

  use EntityRevisionLogTrait;
  use EntityKeysFieldsTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields += static::entityKeysBaseFieldDefinitions($entity_type);
    $fields += static::entityRevisionLogBaseFieldDefinitions();

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('Name')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    return $fields;
  }

}
