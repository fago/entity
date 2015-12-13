<?php

/**
 * @file
 * Contains \Drupal\entity\EntityRevisionTrait.
 */

namespace Drupal\entity;

/**
 * Provides fixes for revisions
 */
trait EntityRevisionTrait {

  /**
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  abstract protected function getEntityType();

  /**
   * @return string
   */
  abstract protected function getEntityTypeId();

  /**
   * @return mixed
   */
  abstract protected function getRevisionId();

  /**
   * Gets an array of placeholders for this entity.
   *
   * Individual entity classes may override this method to add additional
   * placeholders if desired. If so, they should be sure to replicate the
   * property caching logic.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return array
   *   An array of URI placeholders.
   */
  protected function urlRouteParametersWithRevisionSupport($rel) {
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
