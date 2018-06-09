<?php

namespace Drupal\entity\Query;

use Drupal\Core\Session\AccountInterface;

/**
 * Query access handlers control access to entities in queries.
 *
 * An entity defines a query access handler in its annotation:
 * @code
 *   query_access = "\Drupal\entity\Query\QueryAccessHandler"
 * @code
 * The handler builds a set of conditions which are then applied to a query
 * to filter it. For example, if the user #22 only has access to view
 * their own entities, a uid = '22' condition will be built and applied.
 *
 * The following query types are supported:
 * - Entity queries with the $entity_type_id . '_access' tag.
 * - Views queries.
 */
interface QueryAccessHandlerInterface {

  /**
   * Builds the conditions for the given operation and user.
   *
   * @param string $operation
   *   The access operation. One of "view", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to restrict access, or NULL
   *   to assume the current user. Defaults to NULL.
   *
   * @return \Drupal\entity\Query\ConditionGroup
   *   The conditions.
   */
  public function buildConditions($operation, AccountInterface $account = NULL);

}
