<?php

namespace Drupal\entity;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides permissions for entities.
 */
class EntityPermissionProvider implements EntityPermissionProviderInterface, EntityHandlerInterface {

  use StringTranslationTrait;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new EntityPermissionProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $entity_type_label = $entity_type->getPluralLabel();

    $permissions = [];
    $permissions["administer $entity_type_id"] = [
      'title' => $this->t('Administer @type', ['@type' => $entity_type_label]),
      'restrict access' => TRUE,
    ];
    $permissions["access $entity_type_id overview"] = [
      'title' => $this->t('Access @type overview page', ['@type' => $entity_type_label]),
    ];
    if ($entity_type->getPermissionGranularity() == 'entity_type') {
      $permissions += $this->buildEntityTypePermissions($entity_type);
    }
    else {
      $permissions += $this->buildBundlePermissions($entity_type);
    }

    return $permissions;
  }

  /**
   * Builds permissions for the entity_type granularity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   The permissions.
   */
  protected function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $entity_type_label = $entity_type->getPluralLabel();
    $has_owner = $entity_type->isSubclassOf(EntityOwnerInterface::class);

    $permissions = [];
    if ($has_owner) {
      $permissions["create any {$entity_type_id}"] = [
        'title' => $this->t('Create any @type', [
          '@type' => $entity_type_label,
        ]),
      ];
      $permissions["create own {$entity_type_id}"] = [
        'title' => $this->t('Create own @type', [
          '@type' => $entity_type_label,
        ]),
      ];

      $permissions["view any {$entity_type_id}"] = [
        'title' => $this->t('View any @type', [
          '@type' => $entity_type_label,
        ]),
      ];
      $permissions["view own {$entity_type_id}"] = [
        'title' => $this->t('View own @type', [
          '@type' => $entity_type_label,
        ]),
      ];

      $permissions["update any {$entity_type_id}"] = [
        'title' => $this->t('Update any @type', [
          '@type' => $entity_type_label,
        ]),
      ];
      $permissions["update own {$entity_type_id}"] = [
        'title' => $this->t('Update own @type', [
          '@type' => $entity_type_label,
        ]),
      ];

      $permissions["delete any {$entity_type_id}"] = [
        'title' => $this->t('Delete any @type', [
          '@type' => $entity_type_label,
        ]),
      ];
      $permissions["delete own {$entity_type_id}"] = [
        'title' => $this->t('Delete own @type', [
          '@type' => $entity_type_label,
        ]),
      ];
    }
    else {
      $permissions["create {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Create @type', [
          '@type' => $entity_type_label,
        ]),
      ];
      $permissions["view {$entity_type_id}"] = [
        'title' => $this->t('@bundle: View @type', [
          '@type' => $entity_type_label,
        ]),
      ];
      $permissions["update {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Update @type', [
          '@type' => $entity_type_label,
        ]),
      ];
      $permissions["delete {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Delete @type', [
          '@type' => $entity_type_label,
        ]),
      ];
    }

    return $permissions;
  }

  /**
   * Builds permissions for the bundle granularity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   The permissions.
   */
  protected function buildBundlePermissions(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $entity_type_label = $entity_type->getPluralLabel();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $has_owner = $entity_type->isSubclassOf(EntityOwnerInterface::class);

    $permissions = [];
    foreach ($bundles as $bundle_name => $bundle_info) {
      if ($has_owner) {
        $permissions["create any {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Create @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
        $permissions["create own {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Create @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];

        $permissions["view any {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
        $permissions["view own {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];

        $permissions["update any {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Update @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
        $permissions["update own {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Update @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];

        $permissions["delete any {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Delete @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
        $permissions["delete own {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Delete @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
      }
      else {
        $permissions["create {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Create @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
        $permissions["view {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
        $permissions["update {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Update @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
        $permissions["delete {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Delete @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $entity_type_label,
          ]),
        ];
      }
    }

    return $permissions;
  }

}
