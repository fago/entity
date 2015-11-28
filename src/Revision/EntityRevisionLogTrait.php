<?php

/**
 * @file
 * Contains \Drupal\entity\Revision\EntityRevisionLogTrait.
 */

namespace Drupal\entity\Revision;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Provides a trait implementing \Drupal\entity\Revision\EntityRevisionLogInterface.
 */
trait EntityRevisionLogTrait {

  /**
   * Provides the base fields for the entity revision log trait.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected static function entityRevisionLogBaseFieldDefinitions() {
    $fields = [];

    $fields['revision_created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision create time'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setRevisionable(TRUE);

    $fields['revision_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $fields['revision_log_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('Briefly describe the changes you have made.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->revision_created->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->revision_created->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    return $this->revision_user->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUser(UserInterface $account) {
    $this->revision_user->entity = $account;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUserId() {
    return $this->revision_user->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUserId($user_id) {
    $this->revision_user->target_id = $user_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLogMessage() {
    return $this->revision_log_message->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLogMessage($revision_log_message) {
    $this->revision_log_message->value = $revision_log_message;
    return $this;
  }

}
