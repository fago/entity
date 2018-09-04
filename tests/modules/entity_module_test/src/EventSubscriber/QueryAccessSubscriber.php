<?php

namespace Drupal\entity_module_test\EventSubscriber;

use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity.query_access.entity_test_enhanced' => 'onQueryAccess',
    ];
  }

  /**
   * Modifies the access conditions based on the current user.
   *
   * This is just a convenient example for testing. A real subscriber would
   * extend the conditions to cover additional factors, such as a custom entity
   * field.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The event.
   */
  public function onQueryAccess(QueryAccessEvent $event) {
    $conditions = $event->getConditions();
    $account = $event->getAccount();

    if ($account->getEmail() == 'user1@example.com') {
      // This user should not have access to any entities.
      $conditions->alwaysFalse();
    }
    elseif ($account->getEmail() == 'user2@example.com') {
      // This user should have access to entities with the IDs 1, 2, and 3.
      $conditions->alwaysFalse(FALSE);
      $conditions->addCondition('id', ['1', '2', '3']);
    }
  }

}
