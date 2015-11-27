<?php

/**
 * @file
 * Contains \Drupal\entity\Revision\EntityRevisionLogTrait.
 */

namespace Drupal\entity\Revision;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\Entity\User;

/**
 * Provides a trait implementing \Drupal\entity\Revision\EntityRevisionLogInterface.
 */
trait EntityRevisionLogTrait {

  /**
   * Returns whether the entity type has a specific field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   */
  abstract function hasField($field_name);

  /**
   * Provides the base fields for the entity revision log trait.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected static function enhancedEntityRevisionDefaultBaseFields() {
    $fields = [];

    $fields['revision_create'] = BaseFieldDefinition::create('created')
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
    if ($this->hasField('revision_create')) {
      return $this->revision_create->value;
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    if ($this->hasField('revision_create')) {
      $this->revision_create->value = $timestamp;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    if ($this->hasField('revision_user')) {
      return $this->revision_user->entity;
    }
    return User::getAnonymousUser();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUser($user_id) {
    if ($this->hasField('revision_user')) {
      $this->revision_user->target_id = $user_id;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLogMessage() {
    if ($this->hasField('revision_log_message')) {
      return $this->revision_log_message->value;
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLogMessage($revision_log_message) {
    if ($this->hasField('revision_log_message')) {
      $this->revision_log_message->value = $revision_log_message;
    }
    return $this;
  }

}
