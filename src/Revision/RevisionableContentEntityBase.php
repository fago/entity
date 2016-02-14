<?php

/**
 * @file
 * Contains \Drupal\entity\Revision\RevisionableContentEntityBase.
 */

namespace Drupal\entity\Revision;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Provides an entity class with revisions.
 */
abstract class RevisionableContentEntityBase extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = [];

    if ($rel != 'collection') {
      // The entity ID is needed as a route parameter.
      $uri_route_parameters[$this->getEntityTypeId()] = $this->id();
    }
    if (strpos($this->getEntityType()->getLinkTemplate($rel), $this->getEntityTypeId() . '_revision') !== FALSE) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

}
