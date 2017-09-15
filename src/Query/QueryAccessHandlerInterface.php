<?php

namespace Drupal\entity\Query;

use Drupal\Core\Session\AccountInterface;

/**
 * Query access handlers can define conditions which should be applied to any list query.
 *
 * To add a query access handler use
 * @code
 *   query_access = "\Drupal\entity\Query\QueryAccessHandler"
 * @code
 * in your entity annotation.
 *
 * For your actual implementation try to replicate the access logic implemented your access handler.
 */
interface QueryAccessHandlerInterface {

  /**
   * Returns conditions needed for querying a specific entity type.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   *
   * @return \Drupal\entity\Query\Condition
   */
  public function conditions($operation, AccountInterface $account);

}
