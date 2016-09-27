<?php

namespace Drupal\entity_module_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity\Revision\RevisionableContentEntityBase;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Provides a test entity which uses all the capabilities of entity module.
 *
 * @ContentEntityType(
 *   id = "entity_test_owner",
 *   label = @Translation("Entity owner test with enhancements"),
 *   handlers = {
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "\Drupal\entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "\Drupal\entity\Form\RevisionableContentEntityForm",
 *       "edit" = "\Drupal\entity\Form\RevisionableContentEntityForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *       "delete-multiple" = "\Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "list_builder" = "\Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "entity_test_owner",
 *   data_table = "entity_test_owner_field_data",
 *   revision_table = "entity_test_owner_revision",
 *   revision_data_table = "entity_test_owner_field_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   admin_permission = "administer entity_test_owner",
 *   permission_granularity = "bundle",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-page" = "/entity_test_owner/add",
 *     "add-form" = "/entity_test_owner/add/{type}",
 *     "edit-form" = "/entity_test_owner/{entity_test_owner}/edit",
 *     "canonical" = "/entity_test_owner/{entity_test_owner}",
 *     "collection" = "/entity_test_owner",
 *     "delete-multiple-form" = "/entity_test_owner/delete",
 *     "revision" = "/entity_test_owner/{entity_test_owner}/revisions/{entity_test_owner_revision}/view",
 *     "revision-revert-form" = "/entity_test_owner/{entity_test_owner}/revisions/{entity_test_owner_revision}/revert",
 *     "version-history" = "/entity_test_owner/{entity_test_owner}/revisions",
 *   },
 *   bundle_entity_type = "entity_test_owner_bundle",
 * )
 */
class EnhancedOwnerEntity extends RevisionableContentEntityBase implements EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('Name')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The order owner.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\entity_module_test\Entity\EnhancedOwnerEntity::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
