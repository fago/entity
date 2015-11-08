<?php

/**
 * @file
 * Contains \Drupal\src\Controller\EntityCreateController.
 */

namespace Drupal\src\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class EntityCreateController extends ControllerBase {

  /**
   * Displays add custom entity links for available types.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A render array for a list of the custom entity types that can be added or
   *   if there is only one custom entity type defined for the site, the function
   *   returns the custom entity add page for that custom entity type.
   */
  public function addPage($entity_type_id, Request $request) {
    $entity_type = $this->entityManager()->getDefinition($entity_type_id);
    // Get the storage controller for this entity.
    $bundle_storage = $entity_type->getBundleEntityType()
      ? $this->entityManager()->getStorage($entity_type->getBundleEntityType())
      : NULL;
    // Load all entity types for this entity definition.
    $types = $bundle_storage->loadMultiple();

    // Check for existing types.
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($entity_type, $type);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any @entity_label types yet. Go to the <a href=":url">@entity_label type creation page</a> to add a new @entity_label type.', [
          '@entity_label' => $entity_type->getLabel(),
          ':url' => Url::fromRoute('entity.' . $entity_type->getBundleEntityType() . '.add_form')->toString(),
        ]),
      ];
    }

    $build = ['add_links'=>[
      '#theme' => 'links__help',
      '#heading' => [
        'level' => 'h3',
        'text' => $this->t('@entity_label types', [
          '@entity_label' => $entity_type->getLabel(),
        ]),
      ],
      '#links' => [],
    ]];

    $query = $request->query->all();
    foreach ($types as $type) {
      $build['add_links']['#links'][$type->id()] = [
        'title' => $type->label(),
        'url' => new Url('entity.' . $entity_type->id() . '.add_form', ['entity_bundle' => $type->id()], ['query' => $query]),
      ];
    }

    return $build;
  }

  /**
   * Gets the title for the "Entity Add" page.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title customized for this entity type.
   */
  public function getAddPageTitle($entity_type_id) {
    $entity_type = $this->entityManager()->getDefinition($entity_type_id);
    // Get the storage controller for this entity.
    $bundle_storage = $entity_type->getBundleEntityType()
      ? $this->entityManager()->getStorage($entity_type->getBundleEntityType())
      : NULL;
    $types = [];
    if ($bundle_storage) {
      // Load all entity types for this entity definition.
      $types = $bundle_storage->loadMultiple();
    }

    // Check for existing types.
    $entity_bundle  = $types && count($types) == 1 ? reset($types)  : FALSE;

    $args = [
      '@entity_label' => $entity_type->getLabel(),
      '%type' => $entity_bundle ? $entity_bundle->label() : '',
    ];
    if ($entity_bundle) {
      return $this->t('Add %type @entity_label content', $args);
    }
    return $this->t('Add @entity_label', $args);
  }

  /**
   * Presents the custom entity creation form.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_bundle_id
   *   (optional) The entity type bundle ID.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm($entity_type_id, $entity_bundle_id) {
    $entity_type = $this->entityManager()->getDefinition($entity_type_id);

    // Get the entity storage for this entity type.
    $entity_storage = $this->entityManager()->getStorage($entity_type_id);

    $bundle_key = $entity_type->getKey('bundle');
    $entity = $entity_storage->create([
      $bundle_key => $entity_bundle_id,
    ]);
    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Provides the page title for the entity form.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_bundle_id
   *   (optional) The entity type bundle ID.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle($entity_type_id, $entity_bundle_id) {
    $entity_type = $this->entityManager()->getDefinition($entity_type_id);
    $entity_bundle = $this->entityManager()->getStorage($entity_type->getBundleEntityType())->load($entity_bundle_id);
    // Build the form page title using the type.
    return $this->t('Add %type @entity_label', [
      '@entity_label' => $entity_type->getLabel(),
      '%type' => $entity_bundle ? $entity_bundle->label() : 'Invalid Bundle',
    ]);
  }
}
