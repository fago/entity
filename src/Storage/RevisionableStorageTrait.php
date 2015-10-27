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
    // @todo Too bad that you cannot use an entity query here.
    $entity_type = $entity->getEntityType();
    $revision_table = $entity_type->getRevisionTable();
    $revision_field = $entity_type->getKey('revision');
    $id_field = $entity_type->getKey('id');
    $result = $this->database->select($revision_table)
      ->fields($revision_table, [$revision_field])
      ->condition($id_field, $entity->id())
      ->execute()
      ->fetchAllKeyed(0, 0);

    return array_values($result);
  }


}
