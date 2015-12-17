<?php

/**
 * @file
 * Contains \Drupal\entity_module_test\Entity\EnhancedEntityBundle.
 */

namespace Drupal\entity_module_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\entity\Entity\EntityDescriptionInterface;

/**
 * Provides bundles for the test entity.
 *
 * @ConfigEntityType(
 *   id = "entity_test_enhanced_bundle",
 *   label = @Translation("Entity test with enhancments - Bundle"),
 *   handlers = {
 *     "route_provider" = {
 *       "create" = "\Drupal\entity\Routing\CreateHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer entity_test_enhanced",
 *   config_prefix = "entity_test_enhanced_bundle",
 *   bundle_of = "entity_test_enhanced",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description"
 *   },
 *   links = {
 *     "add-form" = "/entity_test_enhanced_bundle/add",
 *   },
 * )
 */
class EnhancedEntityBundle extends ConfigEntityBundleBase implements EntityDescriptionInterface {

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The bundle label.
   *
   * @var string
   */
  protected $label;

  /**
   * The bundle description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
