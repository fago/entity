<?php

namespace Drupal\entity_query_access_test\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Test entity query access class.
 *
 * @ContentEntityType(
 *   id = "entity_query_access_test",
 *   label = @Translation("entity test query access"),
 *   handlers = {
 *     "access" = "\Drupal\entity_test\EntityTestAccessControlHandler",
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *     "view_builder" = "\Drupal\entity_test\EntityTestViewBuilder",
 *     "query_access" = "\Drupal\entity\Query\PerBundleQueryAccessHandler",
 *     "access" = "\Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "\Drupal\entity\EntityPermissionProvider",
 *   },
 *   permission_granularity = "bundle",
 *   base_table = "entity_query_access_test",
 *   render_cache = FALSE,
 *   entity_keys = {
 *     "uuid" = "uuid",
 *     "id" = "id",
 *     "label" = "name",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uid" = "user_id",
 *   }
 * )
 */
class EntityQueryAccessTest extends EntityTest {

}
