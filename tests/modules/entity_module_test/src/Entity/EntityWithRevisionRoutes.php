<?php

/**
 * @file
 * Contains \Drupal\entity_module_test\Entity\EntityWithRevisionRoutes.
 */

namespace Drupal\entity_module_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\EntityKeysFieldsTrait;
use Drupal\entity\Revision\EntityRevisionLogTrait;

/**
 * @ContentEntityType(
 *   id = "entity_test__rev_routes",
 *   label = @Translation("Entity test with revision routes"),
 *   handlers = {
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "route_provider" = {
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test__revision_routes",
 *   data_table = "entity_test__revision_routes__field_data",
 *   revision_table = "entity_test__revision_routes__revision",
 *   revision_data_table = "entity_test__revision_routes__field_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   admin_permission = "administer entity_test__revision_routes",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "revision" = "/entity_test__rev_routes/{entity_test__rev_routes}/revisions/{entity_test__rev_routes_revision}/view",
 *   }
 * )
 */
class EntityWithRevisionRoutes extends ContentEntityBase {

  use EntityRevisionLogTrait;
  use EntityKeysFieldsTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields += static::entityKeysBaseFieldDefinitions($entity_type);

    return $fields;
  }

}
