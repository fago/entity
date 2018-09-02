<?php

namespace Drupal\entity_query_access_test\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Test entity query access class.
 *
 * @ContentEntityType(
 *   id = "entity_uncacheable_query_access",
 *   label = @Translation("entity test uncacheable query access"),
 *   handlers = {
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *     "view_builder" = "\Drupal\entity_test\EntityTestViewBuilder",
 *     "query_access" = "\Drupal\entity\QueryAccess\UncacheableQueryAccessHandler",
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "permission_provider" = "\Drupal\entity\UncacheableEntityPermissionProvider",
 *   },
 *   permission_granularity = "bundle",
 *   base_table = "entity_uncacheable_query_access",
 *   render_cache = FALSE,
 *   entity_keys = {
 *     "uuid" = "uuid",
 *     "id" = "id",
 *     "label" = "name",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uid" = "user_id",
 *     "published" = "status",
 *   }
 * )
 */
class EntityUncacheableQueryAccessTest extends EntityTest implements EntityPublishedInterface {

  use EntityPublishedTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    return $fields + static::publishedBaseFieldDefinitions($entity_type);
  }


}
