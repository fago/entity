<?php

/**
 * @file
 * Contains \Drupal\entity\Controller\RevisionController.
 */

namespace Drupal\entity\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides some controllers related with entity revisions.
 */
class RevisionController extends EntityViewController {

  /**
   * Provides a page to render a single entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity_revision
   *   The Entity to be rendered. Note this variable is named $_entity_revision
   *   rather than $entity to prevent collisions with other named placeholders
   *   in the route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array.
   */
  public function view(EntityInterface $_entity_revision, $view_mode = 'full') {
    return parent::view($_entity_revision, $view_mode);
  }

}
