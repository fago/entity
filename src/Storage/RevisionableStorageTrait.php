<?php

/**
 * @file
 * Contains \Drupal\src\Storage\RevisionableStorageTrait.
 */

namespace Drupal\src\Storage;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implementation of RevisionableStorageInterface using EFQ.
 */
trait RevisionableStorageTrait {

  /**
   * Gets an entity query instance.
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
      ->condition($entity_type->getKey('id'), $entity->id())
      ->execute();
    return array_keys($result);
  }

}
