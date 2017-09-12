<?php

namespace Drupal\entity\Query;

use Drupal\Core\Session\AccountInterface;

interface QueryAccessHandlerInterface {

  /**
   * Returns conditions needed for querying a specific entity type.
   *
   * @param string $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\entity\Query\Condition[]
   */
  public function conditions($operation, AccountInterface $account);

}
