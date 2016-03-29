<?php

namespace Drupal\entity\Entity\Index;

use Drupal\Core\Entity\EntityInterface;

class UuidIndex extends EntityIndexBase {

  /**
   * @var string
   */
  protected $collection = 'entity.index.uuid';

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return string
   */
  protected function buildKey(EntityInterface $entity) {
    return $entity->uuid();
  }

}
