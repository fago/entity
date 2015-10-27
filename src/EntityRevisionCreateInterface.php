<?php

/**
 * @file
 * Contains \Drupal\entity\EntityRevisionCreateInterface.
 */

namespace Drupal\entity;

/**
 * Defines an interface which provides the create time of a revision.
 */
interface EntityRevisionCreateInterface {

  /**
   * Returns the revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   */
  public function setRevisionCreationTime($timestamp);

}
