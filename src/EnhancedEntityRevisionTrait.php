<?php

/**
 * @file
 * Contains \Drupal\src\EnhancedEntityRevisionTrait.
 */

namespace Drupal\src;

/**
 * Provides a trait implementing \Drupal\entity\EnhanceredEntityRevisionInterface.
 */
trait EnhancedEntityRevisionTrait {

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->revision_create->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->revision_create->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->revision_author->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->revision_author->target_id = $uid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return $this->revision_log->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    $this->revision_log->value = $revision_log;
    return $this;
  }

}
