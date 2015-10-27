<?php

/**
 * @file
 * Contains \Drupal\src\EnhancedEntityRevisionInterface.
 */

namespace Drupal\src;

/**
 * Defines an entity type with create/author/log information for revisions.
 */
interface EnhancedEntityRevisionInterface {

  /**
   * Gets the node revision creation timestamp.
   *
   * @return int|NULL
   *   The UNIX timestamp of when this revision was created. Return NULL if the
   *   entity type does not support revision create time.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the node revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the node revision author.
   *
   * @return \Drupal\user\UserInterface|NULL
   *   The user entity for the revision author. Return NULL if the entity type
   *   doesn't support revision authors.
   */
  public function getRevisionAuthor();

  /**
   * Sets the node revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return $this
   */
  public function setRevisionAuthorId($uid);

  /**
   * @todo Ideally this would be its own interface?
   *
   * Returns the entity revision log message.
   *
   * @return string|NULL
   *   The revision log message. Return NULL if the entity type doesn't support
   *   revision logs.
   */
  public function getRevisionLog();

  /**
   * Sets the entity revision log message.
   *
   * @param string $revision_log
   *   The revision log message.
   *
   * @return $this
   */
  public function setRevisionLog($revision_log);

}
