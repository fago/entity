<?php

namespace Drupal\entity\Query;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Query access handler which mimics \Drupal\entity\EntityPermissionProvider.
 *
 * @see \Drupal\entity\EntityAccessControlHandler
 * @see \Drupal\entity\EntityPermissionProvider
 */
class QueryAccessHandler implements EntityHandlerInterface, QueryAccessHandlerInterface {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * DefaultQueryAccessHandler constructor.
   */
  public function __construct(EntityTypeInterface $entityType, EntityTypeBundleInfoInterface $bundleInfo) {
    $this->entityType = $entityType;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function conditions($operation, AccountInterface $account) {
    $condition = new Condition('OR');

    // When we would write down \Drupal\entity\EntityAccessControlHandler::checkEntityOwnerPermissions
    // as boolean logic we would end up with something like this:
    // (view any ... = 1)
    //   || (*.uid = account.uid && operation any ... = 1)
    //   || (*.bundle = bundle1 and (operation any bundle1 == 1))
    //   || (*.bundle = bundle1 and *.uid = account.uid and (operation any bundle1 == 1))
    //   || (*.bundle = bundle2 and (operation any bundle2 == 1))
    
    // No conditions are needed when the user can access all entities anyway.
    $entity_type_id = $this->entityType->id();

    if ($account->hasPermission("administer {$entity_type_id}")) {
      return;
    }

    $has_conditions = FALSE;

    if ($this->entityType->entityClassImplements(EntityPublishedInterface::class) && $this->entityType->entityClassImplements(EntityOwnerInterface::class)) {

      if ($account->hasPermission("$operation {$entity_type_id}")) {
        $has_conditions = TRUE;
        $condition->condition($this->entityType->getKey('published'), 1);

        if ($account->hasPermission("$operation own unpublished {$entity_type_id}")) {
          $condition->condition($this->entityType->getKey('uid'), $account->id());
        }
      }
    }
    elseif ($this->entityType->entityClassImplements(EntityPublishedInterface::class)) {
      if ($account->hasPermission("$operation {$entity_type_id}")) {
        $has_conditions = TRUE;
        $condition->condition($this->entityType->getKey('published'), 1);
      }
    }
    else {
      if ($account->hasPermission("$operation {$entity_type_id}")) {
        $has_conditions = TRUE;
      }
    }

    // When we couldn't apply any conditions we need to deny access, as otherwise we return all
    // results.
    if (!$has_conditions) {
      $condition->condition($this->entityType->getKey('id'), NULL, 'IS NULL');
    }

    return $condition;
  }

}
