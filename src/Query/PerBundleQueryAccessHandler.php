<?php

namespace Drupal\entity\Query;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @see \Drupal\entity\EntityAccessControlHandler
 * @see \Drupal\entity\EntityPermissionProvider
 */
class PerBundleQueryAccessHandler implements EntityHandlerInterface, QueryAccessHandlerInterface {

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
    $condition = new QueryCondition('OR');

    // @todo Implement $operation.
    $entity_type_id = $this->entityType->id();
    $bundle_info = $this->bundleInfo->getBundleInfo($entity_type_id);
    $bundle_key = $this->entityType->getKey('bundle');

    // When we would write down \Drupal\entity\EntityAccessControlHandler::checkEntityOwnerPermissions
    // as boolean logic we would end up with something like this:
    // (view any ... = 1)
    //   || (.uid = account.uid && operation any ... = 1)
    //   || (.bundle = bundle1 and (operation any bundle1 == 1))
    //   || (.bundle = bundle1 and .uid = account.uid and (operation any bundle1 == 1))
    //   || (.bundle = bundle2 and (operation any bundle2 == 1))
    
    // We can though take a couple of shortcuts by checking things in PHP which don't vary per
    // entity.
    
    // No conditions are needed when the user can access all entities anyway.
     if ($account->hasPermission("$operation any {$entity_type_id}")) {
      return;
    }

    if ($this->entityType->entityClassImplements(EntityOwnerInterface::class)) {
      $uid_key = $this->entityType->getKey('uid');

      $has_conditions = FALSE;

      // View own $entity_type permission.
      if ($account->hasPermission("$operation own ${entity_type_id}")) {
        $has_conditions = TRUE;
        $condition->condition($uid_key, $account->id());
      }

      // View any $bundle permission
      $bundles_with_view_any_permission = array_filter(array_keys($bundle_info), function ($bundle) use ($account, $operation, $entity_type_id) {
        return $account->hasPermission("$operation any $bundle $entity_type_id");
      });
      if ($bundles_with_view_any_permission) {
        $has_conditions = TRUE;
        $condition->condition($bundle_key, $bundles_with_view_any_permission);
      }

      // View own $bundle permission
      foreach (array_keys($bundle_info) as $bundle) {
        if ($account->hasPermission("$operation own $bundle $entity_type_id")) {
          $inner_condition = (new QueryCondition('AND'))
            ->condition($bundle_key, $bundle)
            ->condition($uid_key, $account->id());
          $condition->condition($inner_condition);
          $has_conditions = TRUE;
        }
      }

      // When we couldn't apply any conditions we need to deny access.
      if (!$has_conditions) {
        $condition->condition($this->entityType->getKey('id'), NULL, 'IS NULL');
      }
    }
    else {
      $bundles_with_view_any_permission = array_filter(array_keys($bundle_info),
        function ($bundle) use ($account, $operation, $entity_type_id) {
          return $account->hasPermission("$operation any $bundle $entity_type_id");
        });
      if ($bundles_with_view_any_permission) {
        $condition->condition($bundle_key, $bundles_with_view_any_permission);
      }
      else {
        // @fixme
        // We need to deny access in that case.
        // $condition->condition('1', '0');
        // $condition->condition($this->entityType->getKey('id'), NULL, 'IS NULL');
      }
    }

    // @todo In the future take into account EntityPublishedInterface, just like
    // \Drupal\node\Plugin\views\filter\Status::query does.
    // if ($this->entityType instanceof EntityPublishedInterface) { }

    return $condition;
  }

}
