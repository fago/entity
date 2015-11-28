<?php

/**
 * @file
 * Contains \Drupal\entity\Revision\EntityRevisionLogInterface.
 */

namespace Drupal\entity\Revision;

/**
 * Defines an entity type with create time/author/log information for revisions.
 */
interface EntityRevisionLogInterface {

  /**
   * Gets the entity revision creation timestamp.
   *
   * @return int|NULL
   *   The UNIX timestamp of when this revision was created. Return NULL if the
   *   entity type does not support revision create time.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the entity revision author.
   *
   * @return \Drupal\user\UserInterface|NULL
   *   The user entity for the revision author. Return NULL if the entity type
   *   doesn't support revision authors.
   */
  public function getRevisionUser();

  /**
   * Sets the entity revision author.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account of the revision author.
   *
   * @return $this
   */
  public function setRevisionUser($account);

  /**
   * Gets the entity revision author ID.
   *
   * @return int
   *   The user ID.
   */
  public function getRevisionUserId();

  /**
   * Sets the entity revision author by ID.
   *
   * @param int $user_id
   *   The user ID of the revision author.
   *
   * @return $this
   */
  public function setRevisionUserId($user_id);

  /**
   * Returns the entity revision log message.
   *
   * @return string|NULL
   *   The revision log message. Return NULL if the entity type doesn't support
   *   revision logs.
   */
  public function getRevisionLogMessage();

  /**
   * Sets the entity revision log message.
   *
   * @param string $revision_log_message
   *   The revision log message.
   *
   * @return $this
   */
  public function setRevisionLogMessage($revision_log_message);

}
