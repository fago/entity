<?php

namespace Drupal\entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

class EntityServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Add a special factory to be able to override the used query class.
    $container->getDefinition('entity.query.sql')
      ->setClass('Drupal\entity\Query\EntityQueryFactory');
  }

}
