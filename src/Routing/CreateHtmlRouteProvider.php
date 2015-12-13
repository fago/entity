<?php

/**
 * @file
 * Contains \Drupal\entity\Routing\CreateHtmlRouteProvider.
 */

namespace Drupal\entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides HTML routes for creating entities.
 *
 * This class provides the following routes for entities, with title callbacks:
 * - add-page
 * - add-form
 *
 * @see \Drupal\entity\Routing\AdminCreateHtmlRouteProvider.
 */
class CreateHtmlRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = new RouteCollection();
    if ($route = $this->addPageRoute($entity_type)) {
      $routes->add('entity.' . $entity_type->id() . '.add_page', $route);
    }
    if ($route = $this->addFormRoute($entity_type)) {
      $routes->add('entity.' . $entity_type->id() . '.add_form', $route);
    }

    return $routes;
  }

  /**
   * Returns the add page route.
   *
   * Built only for entity types that have bundles.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function addPageRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-page') && $entity_type->getKey('bundle')) {
      $route = new Route($entity_type->getLinkTemplate('add-page'));
      $route->setDefault('_controller', '\Drupal\entity\Controller\EntityCreateController::addPage');
      $route->setDefault('_title_callback', '\Drupal\entity\Controller\EntityCreateController::addPageTitle');
      $route->setDefault('entity_type_id', $entity_type->id());
      $route->setRequirement('_entity_create_access', $entity_type->id());

      return $route;
    }
  }

  /**
   * Returns the add form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function addFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form')) {
      $route = new Route($entity_type->getLinkTemplate('add-form'));
      $route->setDefault('_controller', '\Drupal\entity\Controller\EntityCreateController::addForm');
      $route->setDefault('_title_callback', '\Drupal\entity\Controller\EntityCreateController::addFormTitle');
      $route->setDefault('entity_type_id', $entity_type->id());
      $route->setRequirement('_entity_create_access', $entity_type->id());

      return $route;
    }
  }

}
