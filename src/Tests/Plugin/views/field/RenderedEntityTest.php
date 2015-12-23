<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\Plugin\views\field\RenderedEntityTest.
 */

namespace Drupal\entity\Tests\Plugin\views\field;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\Core\Entity\Entity\EntityViewMode;

/**
 * Tests the Drupal\entity\Plugin\views\field\RenderedEntity handler.
 *
 * @group entity
 */
class RenderedEntityTest extends ViewKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'entity_module_test', 'field'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_rendered'];

  /**
   * The logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['entity_module_test']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['entity_test']);

    EntityViewMode::create([
      'id' => 'entity_test.foobar',
      'targetEntityType' => 'entity_test',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'My view mode',
    ])->save();

    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'foobar',
      'label' => 'My view mode',
      'status' => TRUE,
    ]);
    $display->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'string',
    ]);
    $field_storage->save();

    $field_config = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field_config->save();

    // Create some test entities.
    for ($i = 1; $i <= 3; $i++) {
      EntityTest::create([
        'name' => "Article title $i",
        'test_field' => "Test $i",
      ])->save();
    }

    $role = Role::create([
      'id' => 'test_role',
    ]);
    $role->grantPermission('bypass node access');
    $role->save();
    $this->user = User::create([
      'name' => 'test user',
    ]);
    $this->user->addRole($role->id());
    $this->user->save();

    parent::setUpFixtures();
  }

  /**
   * Tests the default rendered entity output.
   */
  public function testRenderedEntityWithoutField() {
    \Drupal::currentUser()->setAccount($this->user);

    EntityViewDisplay::load('entity_test.entity_test.foobar')
      ->removeComponent('test_field')
      ->save();

    // The view should not display the body field.
    $view = Views::getView('test_field_entity_test_rendered');
    $build = $view->preview();
    $renderer = \Drupal::service('renderer');
    $renderer->renderPlain($build);
    for ($i = 1; $i <= 3; $i++) {
      $view_field = $view->style_plugin->getField($i - 1, 'rendered_entity');
      $search_result = strpos($view_field, "Test $i") !== FALSE;
      $this->assertFalse($search_result, "The text 'Test $i' not found in the view.");
    }
  }

  /**
   * Tests the rendered entity output with the body field configured to show.
   */
  public function testRenderedEntityWithField() {
    \Drupal::currentUser()->setAccount($this->user);

    // Show the body on the node.x.foobar view mode.
    EntityViewDisplay::load('entity_test.entity_test.foobar')->setComponent('test_field', ['type' => 'string', 'label' => 'above'])->save();

    // The view should display the body field.
    $view = Views::getView('test_field_entity_test_rendered');
    $build = $view->preview();
    $renderer = \Drupal::service('renderer');
    $renderer->renderPlain($build);
    for ($i = 1; $i <= 3; $i++) {
      $view_field = $view->style_plugin->getField($i - 1, 'rendered_entity');
      $search_result = strpos($view_field, "Test $i") !== FALSE;
      $this->assertTrue($search_result, "The text 'Test $i' found in the view.");
    }
  }

}
