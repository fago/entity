<?php

namespace Drupal\entity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for generating per-bundle CRUD permissions.
 */
class EntityPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new EntityPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Generates permissions for entity types.
   */
  public function buildPermissions() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, function ($entity_type) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type->id());
      $permissions_generate = TRUE;
      if ($entity_type->hasKey('permissions_generate')) {
        $permissions_generate = $entity_type->get('permissions_generate');
      }

      return $access_control_handler instanceof EntityAccessControlHandler && $permissions_generate;
    });

    $permissions = [];
    foreach (array_keys($entity_types) as $entity_type_id) {
      $permissions += $this->getPermissions($entity_type_id);
    }
    return $permissions;
  }

  /**
   * Gets the permissions.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *    Returns an array of permissions.
   */
  public function getPermissions($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $permissions["administer $entity_type_id"] = [
      'title' => $this->t('Administer @type', ['@type' => $entity_type->getPluralLabel()]),
      'restrict access' => TRUE,
    ];
    $permissions["bypass $entity_type_id access"] = [
      'title' => $this->t('View, edit and delete all @type regardless of permission restrictions.', ['@type' => $entity_type->getPluralLabel()]),
      'restrict access' => TRUE,
    ];
    $permissions["access $entity_type_id overview"] = [
      'title' => $this->t('Access @type overview page', ['@type' => $entity_type->getPluralLabel()]),
    ];
    if ($entity_type->getPermissionGranularity() == 'entity_type') {
      $permissions += $this->getEntityTypePermissions($entity_type_id);
    }
    else {
      $permissions += $this->getBundlePermissions($entity_type_id);
    }

    return $permissions;
  }

  /**
   * Gets the permissions for entity_type granularity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   The array of permissions.
   */
  protected function getEntityTypePermissions($entity_type_id) {
    $permissions = [];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $has_owner = $entity_type->isSubclassOf(EntityOwnerInterface::class);

    if ($has_owner) {
      $permissions["create any {$entity_type_id}"] = [
        'title' => $this->t('Create any @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
      $permissions["create own {$entity_type_id}"] = [
        'title' => $this->t('Create own @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];

      $permissions["view any {$entity_type_id}"] = [
        'title' => $this->t('View any @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
      $permissions["view own {$entity_type_id}"] = [
        'title' => $this->t('View own @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];

      $permissions["update any {$entity_type_id}"] = [
        'title' => $this->t('Update any @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
      $permissions["update own {$entity_type_id}"] = [
        'title' => $this->t('Update own @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];

      $permissions["delete any {$entity_type_id}"] = [
        'title' => $this->t('Delete any @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
      $permissions["delete own {$entity_type_id}"] = [
        'title' => $this->t('Delete own @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
    }
    else {
      $permissions["create {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Create @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
      $permissions["view {$entity_type_id}"] = [
        'title' => $this->t('@bundle: View @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
      $permissions["update {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Update @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
      $permissions["delete {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Delete @type', [
          '@type' => $entity_type->getPluralLabel(),
        ]),
      ];
    }

    return $permissions;
  }

  /**
   * Gets the permissions for bundle granularity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   The array of permissions.
   */
  protected function getBundlePermissions($entity_type_id) {
    $permissions = [];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $has_owner = $entity_type->isSubclassOf(EntityOwnerInterface::class);

    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $bundle_entity_type_id = $entity_type->getBundleEntityType();
    $bundle_type_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
    foreach (array_keys($bundles) as $bundle_id) {
      $bundle = $bundle_type_storage->load($bundle_id);

      if ($has_owner) {
        $permissions["create any {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Create @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
        $permissions["create own {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Create @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];

        $permissions["view any {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
        $permissions["view own {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];

        $permissions["update any {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Update @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
        $permissions["update own {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Update @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];

        $permissions["delete any {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Delete @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
        $permissions["delete own {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Delete @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
      }
      else {
        $permissions["create {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Create @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
        $permissions["view {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
        $permissions["update {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Update @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
        $permissions["delete {$bundle_id} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: Delete @type', [
            '@bundle' => $bundle->label(),
            '@type' => $entity_type->getPluralLabel(),
          ]),
        ];
      }
    }

    return $permissions;
  }

}
