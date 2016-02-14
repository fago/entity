<?php

/**
 * @file
 * Contains \Drupal\entity\Controller\RevisionControllerTrait.
 */

namespace Drupal\entity\Controller;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a trait for common revision UI functionality.
 */
trait RevisionControllerTrait {

  use StringTranslationTrait;

  /**
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  abstract protected function entityTypeManager();

  /**
   * @return \Drupal\Core\Language\LanguageManagerInterface
   */
  public abstract function languageManager();

  /**
   * Determines if the user has permission to revert revisions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check revert access for.
   *
   * @return bool
   *   TRUE if the user has revert access.
   */
  abstract protected function hasRevertRevisionAccess(EntityInterface $entity);

  /**
   * Determines if the user has permission to delete revisions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check delete revision access for.
   *
   * @return bool
   *   TRUE if the user has delete revision access.
   */
  abstract protected function hasDeleteRevisionAccess(EntityInterface $entity);

  /**
   * Builds a link to revert an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_revision
   *   The entity to build a revert revision link for.
   *
   * @return array A link render array.
   * A link render array.
   * @internal param int $revision_id The revision ID of the revert link.*   The revision ID of the revert link.
   *
   */
  abstract protected function buildRevertRevisionLink(EntityInterface $entity_revision);

  /**
   * Builds a link to delete an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_revision
   *   The entity to build a delete revision link for.
   *
   * @return array A link render array.
   * A link render array.
   * @internal param int $revision_id The revision ID of the delete link.*   The revision ID of the delete link.
   *
   */
  abstract protected function buildDeleteRevisionLink(EntityInterface $entity_revision);

  /**
   * Returns a string providing details of the revision.
   *
   * E.g. Node describes its revisions using {date} by {username}. For the
   *   non-current revision, it also provides a link to view that revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   * @param bool $is_current
   *   TRUE if the revision is the current revision.
   *
   * @return string
   *   Returns a string to provide the details of the revision.
   */
  abstract protected function getRevisionDescription(ContentEntityInterface $revision, $is_current = FALSE);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return array
   */
  protected function revisionIds(ContentEntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $result = $this->entityTypeManager()->getStorage($entity_type)->getQuery()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->sort($entity_type->getKey('revision'), 'DESC')
      ->execute();
    return array_keys($result);
  }

  /**
   * Generates an overview table of older revisions of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ContentEntityInterface $entity) {
    $langcode = $this->languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    /** @var \Drupal\content_entity_base\Entity\Storage\RevisionableStorageInterface $entity_storage */
    $entity_storage = $this->entityTypeManager()
      ->getStorage($entity->getEntityTypeId());

    $header = [$this->t('Revision'), $this->t('Operations')];
    $rows = [];

    $revision_ids = $this->revisionIds($entity);
    // @todo Expand the entity storage to load multiple revisions.
    $entity_revisions = array_combine($revision_ids, array_map(function($vid) use ($entity_storage) {
      return $entity_storage->loadRevision($vid);
      }, $revision_ids));

    $latest_revision = TRUE;

    foreach ($entity_revisions as $revision) {
      $row = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
          ->isRevisionTranslationAffected()
      ) {
        if ($latest_revision) {
          $row[] = $this->getRevisionDescription($revision, TRUE);
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $row[] = $this->getRevisionDescription($revision, FALSE);
          $links = $this->getOperationLinks($revision);

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }
      }

      $rows[] = $row;
    }

    $build[$entity->getEntityTypeId() . '_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    // We have no clue about caching yet.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Get the links of the operations for an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_revision
   *   The entity to build the revision links for.
   *
   * @return array
   *   The operation links.
   */
  protected function getOperationLinks(EntityInterface $entity_revision) {
    $links = [];
    $revert_permission = $this->hasRevertRevisionAccess($entity_revision);
    $delete_permission = $this->hasDeleteRevisionAccess($entity_revision);
    if ($revert_permission) {
      $links['revert'] = $this->buildRevertRevisionLink($entity_revision);
    }

    if ($delete_permission) {
      $links['delete'] = $this->buildDeleteRevisionLink($entity_revision);
    }
    return $links;
  }

}
