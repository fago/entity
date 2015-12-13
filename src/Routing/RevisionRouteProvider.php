<?php

/**
 * @file
 * Contains \Drupal\entity\Routing\RevisionRouteProvider.
 */

namespace Drupal\entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides revision routes.
 */
class RevisionRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();
    $entity_type_id = $entity_type->id();
    if ($view_route = $this->getRevisionViewRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.revision", $view_route);
    }

    return $collection;
  }

  /**
   * Gets the entity revision view route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionViewRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('revision'));
      $route->addDefaults([
        '_controller' => '\Drupal\entity\Controller\RevisionController::view',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ]);
      $route->addRequirements([
        '_entity_access_revision' => "$entity_type_id.view",
      ]);
      $route->setOption('parameters', [
        $entity_type->id() => [
          'type' => 'entity:' . $entity_type->id(),
        ],
        $entity_type->id() . '_revision' => [
          'type' => 'entity_revision:' . $entity_type->id(),
        ],
      ]);
      return $route;
    }
  }

}
