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
 *     "form" = {
 *       "add" = "\Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "\Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *       "create" = "\Drupal\entity\Routing\CreateHtmlRouteProvider",
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
 *     "bundle" = "type",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "add-page" = "/entity_test_enhanced/add",
 *     "add-form" = "/entity_test_enhanced/add/{type}",
 *     "revision" = "/entity_test_enhanced/{entity_test_enhanced}/revisions/{entity_test_enhanced_revision}/view",
 *   },
 *   bundle_entity_type = "entity_test_enhanced_bundle"
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
