<?php

namespace Drupal\entity_module_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\entity\Entity\RevisionableEntityBundleInterface;

/**
 * Provides bundles for the test entity.
 *
 * @ConfigEntityType(
 *   id = "entity_test_owner_bundle",
 *   label = @Translation("Entity owner test with enhancements - Bundle"),
 *   admin_permission = "administer entity_test_enhanced_owner",
 *   config_prefix = "entity_test_owner_bundle",
 *   bundle_of = "entity_test_owner",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description"
 *   },
 * )
 */
class EnhancedOwnerEntityBundle extends ConfigEntityBundleBase implements EntityDescriptionInterface, RevisionableEntityBundleInterface {

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
   * Should new entities of this bundle have a new revision by default.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

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

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

}
