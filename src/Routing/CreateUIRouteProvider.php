<?php

/**
 * @file
 * Contains \Drupal\entity\Routing\CreateUIRouteProvider.
 */

namespace Drupal\entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Additional common routes needed for a CRUD UI.
 *
 * - add bundle add overview page (like /node/add)
 * - a generic bundle specific entity add form (like /node/add/page)
 */
class CreateUIRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = new RouteCollection();

    if ($route = $this->collectionRoute($entity_type)) {
      $routes->add('entity.' . $entity_type->id() . '.collection', $route);
    }
    if ($route = $this->addPageRoute($entity_type)) {
      $routes->add('entity.' . $entity_type->id() . '.add_page', $route);
    }
    if ($route = $this->addFormRoute($entity_type)) {
      $routes->add('entity.' . $entity_type->id() . '.add_form', $route);
    }

    return $routes;
  }

  /**
   * Returns the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return \Symfony\Component\Routing\Route|null
   */
  protected function collectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection')) {
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route->setDefault('_title', $entity_type->getLabel() . ' content');
      $route->setDefault('_entity_list', $entity_type->id());

      if ($admin_permission = $entity_type->getAdminPermission()) {
        $route->setRequirement('_permission', $entity_type->getAdminPermission());
      }

      return $route;
    }
  }

  /**
   * Returns the add page route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return \Symfony\Component\Routing\Route|null
   */
  protected function addPageRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-page')) {
      $route = new Route($entity_type->getLinkTemplate('add-page'));
      $route->setDefault('_controller', '\Drupal\src\Controller\EntityCreateController::addPage');
      $route->setDefault('_title_callback', '\Drupal\src\Controller\EntityCreateController::getAddPageTitle');
      $route->setDefault('entity_definition', $entity_type->id());
      $route->setOption('parameters', ['entity_definition' => ['type' => 'entity_definition']]);
      $route->setRequirement('_entity_create_access', $entity_type->id());
      return $route;
    }
  }

  /**
   * Returns the add form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return \Symfony\Component\Routing\Route|null
   */
  protected function addFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form')) {
      $route = new Route('entity.' . $entity_type->id() . '.add-form');
      $route->setDefault('_controller', '\Drupal\src\Controller\EntityCreateController::addForm');
      $route->setDefault('_title_callback', '\Drupal\src\Controller\EntityCreateController::getAddFormTitle');
      $route->setDefault('entity_type', $entity_type->id());
      $route->setRequirement('_entity_create_access', $entity_type->id());
      return $route;
    }
  }

}
