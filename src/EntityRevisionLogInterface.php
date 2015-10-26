<?php

/**
 * @file
 * Contains \Drupal\entity\EntityRevisionLogInterface.
 */

namespace Drupal\entity;

/**
 * Provides an interface for entities which have revisions with logging.
 */
interface EntityRevisionLogInterface {

  /**
   * Returns the entity revision log message.
   *
   * @return string
   *   The revision log message.
   */
  public function getRevisionLog();

  /**
   * Sets the entity revision log message.
   *
   * @param string $revision_log
   *   The revision log message.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setRevisionLog($revision_log);

}
