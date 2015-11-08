<?php

/**
 * @file
 * Contains \Drupal\entity\Revision\EnhancedEntityRevisionTrait.
 */

namespace Drupal\entity\Revision;
use Drupal\user\Entity\User;

/**
 * Provides a trait implementing \Drupal\entity\EnhanceredEntityRevisionInterface.
 */
trait EnhancedEntityRevisionTrait {

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
  public function getRevisionAuthor() {
    if ($this->hasField('revision_author')) {
      return $this->revision_author->entity;
    }
    return User::getAnonymousUser();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    if ($this->hasField('revision_author')) {
      $this->revision_author->target_id = $uid;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    if ($this->hasField('revision_log')) {
      return $this->revision_log->value;
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    if ($this->hasField('revision_log')) {
      $this->revision_log->value = $revision_log;
    }
    return $this;
  }

}
