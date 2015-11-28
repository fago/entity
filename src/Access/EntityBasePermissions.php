<?php

/**
 * @file
 * Contains \Drupal\entity\Access\EntityBasePermissions.
 */

namespace Drupal\entity\Entity\Access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class containing permission callbacks.
 */
class EntityBasePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

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
   * Creates Drupal\entity\Entity\Access\EntityBasePermissions.
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
   * Gets an array of entity type permissions.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The custom entity definition.
   *
   * @return array
   *   The entity type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function entityPermissions(ContentEntityTypeInterface $entity_type) {
    $perms = [];

    if (!empty($entity_type)) {
      // Get the entity ID.
      $entity_type_id = $entity_type->id();
      // Build replacement data for lables and descriptions.
      $replacements = [
        '@entity_type_id' => $entity_type_id,
        '@entity_label' => $entity_type->getLabel(),
      ];
      // Add the default entity permissions.
      $perms = [
        "bypass $entity_type_id access" => [
          'title' => $this->t('Bypass @entity_label access control', $replacements),
          'description' => $this->t('View, edit and delete all @entity_label regardless of permission restrictions.', $replacements),
          'restrict access' => TRUE,
        ],
        "administer $entity_type_id types" => [
          'title' => $this->t('Administer @entity_label types', $replacements),
          'description' => $this->t('Promote, change ownership, edit revisions, and perform other tasks across all @entity_label types.', $replacements),
          'restrict access' => TRUE,
        ],
        "administer $entity_type_id" => [
          'title' => $this->t('Administer @entity_label', $replacements),
          'restrict access' => TRUE,
        ],
        "access $entity_type_id overview" => [
          'title' => $this->t('Access the @entity_label overview page', $replacements),
          'description' => $this->t('Get an overview of all @entity_label.', $replacements),
        ],
        "access $entity_type_id" => [
          'title' => $this->t('View published @entity_label', $replacements),
        ],
        "view own unpublished $entity_type_id" => [
          'title' => $this->t('View own unpublished @entity_label', $replacements),
        ],
        "view all $entity_type_id revisions" => [
          'title' => $this->t('View all @entity_label revisions', $replacements),
        ],
        "revert all $entity_type_id revisions" => [
          'title' => $this->t('Revert all @entity_label revisions', $replacements),
          'description' => $this->t('Role requires permission <em>View all @entity_label revisions</em> and <em>edit rights</em> for @entity_label in question or <em>Administer @entity_label</em>.', $replacements),
        ],
        "delete all $entity_type_id revisions" => [
          'title' => $this->t('Delete all @entity_label revisions', $replacements),
          'description' => $this->t('Role requires permission to <em>View all @entity_label revisions</em> and <em>delete rights</em> for @entity_label in question or <em>Administer @entity_label</em>.', $replacements),
        ],
      ];
      // Load bundles if any are defined.
      // Generate entity permissions for all types for this entity.
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_id => $bundle_info) {
        $perms += $this->buildPermissions($entity_type, $bundle_id, $bundle_info);
      }
    }

    return $perms;
  }

  /**
   * Builds a standard list of entity permissions for a given type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $bundle_id
   *   The bundle ID.
   * @param array $bundle_info
   *   The bundle information.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(EntityTypeInterface $entity_type, $bundle_id, array $bundle_info) {
    $base_entity_type_id = $entity_type->id();
    $type_params = [
      '%entity_label' => $entity_type->getLabel(),
      '%bundle_id' => $bundle_info['label'],
    ];

    return [
      "create $bundle_id $base_entity_type_id" => [
        'title' => $this->t('%bundle_id: Create new %entity_label', $type_params),
      ],
      "edit own $bundle_id $base_entity_type_id" => [
        'title' => $this->t('%bundle_id: Edit own %entity_label', $type_params),
      ],
      "edit any $bundle_id $base_entity_type_id" => [
        'title' => $this->t('%bundle_id: Edit any %entity_label', $type_params),
      ],
      "delete own $bundle_id $base_entity_type_id" => [
        'title' => $this->t('%bundle_id: Delete own %entity_label', $type_params),
      ],
      "delete any $bundle_id $base_entity_type_id" => [
        'title' => $this->t('%bundle_id: Delete any %entity_label', $type_params),
      ],
      "view $bundle_id $base_entity_type_id revisions" => [
        'title' => $this->t('%bundle_id: View %entity_label revisions', $type_params),
      ],
      "revert $bundle_id $base_entity_type_id revisions" => [
        'title' => $this->t('%bundle_id: Revert %entity_label revisions', $type_params),
        'description' => t('Role requires permission <em>view revisions</em> and <em>edit rights</em> for %entity_label in question, or <em>Administer %entity_label</em>.', $type_params),
      ],
      "delete $bundle_id $base_entity_type_id revisions" => [
        'title' => $this->t('%bundle_id: Delete %entity_label revisions', $type_params),
        'description' => $this->t('Role requires permission to <em>view revisions</em> and <em>delete rights</em> for %entity_label in question, or <em>Administer %entity_label</em>.', $type_params),
      ],
    ];
  }
}
