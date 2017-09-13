<?php

namespace Drupal\entity\Query;

use Drupal\Core\Entity\Query\Sql\QueryFactory;

/**
 * \Drupal\Core\Entity\Query\Sql\QueryFactory::__construct will
 * add namespaces for the current class, so it is able to find
 * \Drupal\entity\Query\Query.
 */
class EntityQueryFactory extends QueryFactory {
  
}
