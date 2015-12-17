<?php

/**
 * @file
 * Contains \Drupal\entity\Controller\EntityCreateController.
 */

namespace Drupal\entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A generic controller for creating entities.
 */
class EntityCreateController extends ControllerBase {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EntityCreateController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, RendererInterface $renderer) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add links for the available bundles.
   *
   * Redirects to the add form if there's only one bundle available.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   If there's only one available bundle, a redirect response.
   *   Otherwise, a render array with the add links for each bundle.
   */
  public function addPage($entity_type_id, Request $request) {
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
    $bundle_type = $entity_type->getBundleEntityType();
    $bundle_key = $entity_type->getKey('bundle');
    $form_route_name = 'entity.' . $entity_type_id . '.add_form';
    $build = [
      '#theme' => 'entity_add_list',
      '#cache' => [
        'tags' => $entity_type->getListCacheTags(),
      ],
      '#bundle_type' => $bundle_type,
    ];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    // Filter out the bundles the user doesn't have access to.
    $access_control_handler = $this->entityTypeManager()->getAccessControlHandler($bundle_type);
    foreach ($bundles as $bundle_name => $bundle_info) {
      $access = $access_control_handler->createAccess($bundle_name, NULL, [], TRUE);
      if (!$access->isAllowed()) {
        unset($bundles[$bundle_name]);
      }
      $this->renderer->addCacheableDependency($build, $access);
    }
    // Redirect if there's only one bundle available.
    if (count($bundles) == 1) {
      $bundle_names = array_keys($bundles);
      $bundle_name = reset($bundle_names);
      return $this->redirect($form_route_name, [$bundle_key => $bundle_name]);
    }
    // Prepare the #bundles array for the template.
    $bundles = $this->loadBundleDescriptions($bundles, $bundle_type);
    foreach ($bundles as $bundle_name => $bundle_info) {
      $build['#bundles'][$bundle_name] = [
        'label' => $bundle_info['label'],
        'description' => $bundle_info['description'],
        'add_link' => Link::createFromRoute($bundle_info['label'], $form_route_name, [$bundle_key => $bundle_name]),
      ];
    }

    return $build;
  }

  /**
   * The title callback for the add page.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
    return $this->t('Add @entity-type', ['@entity-type' => $entity_type->getLowercaseLabel()]);
  }

  /**
   * Provides the add form for an entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The add form.
   */
  public function addForm($entity_type_id, RouteMatchInterface $route_match) {
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
    $values = [];
    // Entities of this type have bundles, one was provided in the url.
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $bundle_name = $route_match->getRawParameter($bundle_key);
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      if (empty($bundle_name) || !isset($bundles[$bundle_name])) {
        // The bundle parameter is invalid.
        throw new NotFoundHttpException();
      }
      $values[$bundle_key] = $bundle_name;
    }
    $entity = $this->entityTypeManager()->getStorage($entity_type_id)->create($values);

    return $this->entityFormBuilder()->getForm($entity, 'add');
  }

  /**
   * The title callback for the add form.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle($entity_type_id, RouteMatchInterface $route_match) {
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
    $bundle_key = $entity_type->getKey('bundle');
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    if ($bundle_key && count($bundles) > 1) {
      $bundle_name = $route_match->getRawParameter($bundle_key);
      $title = $this->t('Add @bundle', ['@bundle' => $bundles[$bundle_name]['label']]);
    }
    else {
      $title = $this->t('Add @entity-type', ['@entity-type' => $entity_type->getLowercaseLabel()]);
    }

    return $title;
  }

  /**
   * Expands the bundle information with descriptions, if known.
   *
   * @param array $bundles
   *   An array of bundle information.
   * @param string $bundle_type
   *   The id of the bundle entity type.
   *
   * @return array
   *   The expanded array of bundle information.
   */
  protected function loadBundleDescriptions(array $bundles, $bundle_type) {
    // Ensure the presence of the description key.
    foreach ($bundles as $bundle_name => &$bundle_info) {
      $bundle_info['description'] = '';
    }
    // Only bundles provided by entity types have descriptions.
    if (empty($bundle_type)) {
      return $bundles;
    }
    $bundle_entity_type = $this->entityTypeManager()->getDefinition($bundle_type);
    if (!$bundle_entity_type->isSubclassOf('\Drupal\entity\Entity\EntityDescriptionInterface')) {
      return $bundles;
    }
    $bundle_names = array_keys($bundles);
    $bundle_entities = $this->entityTypeManager->getStorage($bundle_type)->loadMultiple($bundle_names);
    foreach ($bundles as $bundle_name => &$bundle_info) {
      if (isset($bundle_entities[$bundle_name])) {
        $bundle_info['description'] = $bundle_entities[$bundle_name]->getDescription();
      }
    }

    return $bundles;
  }

}
