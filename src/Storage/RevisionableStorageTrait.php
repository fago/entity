<?php

/**
 * @file
 * Contains \Drupal\entity\Storage\RevisionableStorageTrait.
 */

namespace Drupal\entity\Storage;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implementation of RevisionableStorageInterface using EFQ.
 */
trait RevisionableStorageTrait {

  /**
   * Gets an entity query instance.
   *
   * @param string $conjunction
   *   (optional) The logical operator for the query, either:
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query instance.
   */
  abstract public function getQuery($conjunction = 'AND');

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ContentEntityInterface $entity) {
    $entity_type = $entity->getEntityType();
    $count = $this->getQuery()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->condition($entity_type->getKey('default_langcode'), 1)
      ->count()
      ->execute();
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ContentEntityInterface $entity) {
    $result = $this->getQuery()
      ->allRevisions()
      ->condition($entity->getEntityType()->getKey('id'), $entity->id())
      ->execute();
    return array_keys($result);
  }

}
