<?php

/**
 * @file
 * Contains \Drupal\entity\Routing\AdminCreateHtmlRouteProvider.
 */

namespace Drupal\entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides HTML routes for creating entities using the administrative theme.
 */
class AdminCreateHtmlRouteProvider extends CreateHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function addPageRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::addPageRoute($entity_type)) {
      $route->setOption('_admin_route', TRUE);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::addFormRoute($entity_type)) {
      $route->setOption('_admin_route', TRUE);
      return $route;
    }
  }

}
