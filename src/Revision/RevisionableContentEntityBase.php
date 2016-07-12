<?php

namespace Drupal\entity\Revision;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase as BaseRevisionableContentEntityBase;

/**
 * Improves the url route handling of core's revisionable content entity base.
 */
abstract class RevisionableContentEntityBase extends BaseRevisionableContentEntityBase {

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

  /**
   * @inheritDoc
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly, make the entity owner the
    // revision author.
    if (($uid = $this->getEntityKey('uid'))) {
      $this->setRevisionUserId($uid);
    }
    $this->setRevisionCreationTime(time());
  }


}
