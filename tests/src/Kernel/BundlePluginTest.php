<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_module_bundle_plugin_test\Entity\EntityTestBundlePlugin;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the bundle plugin API.
 *
 * @group entity
 */
class BundlePluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'entity',
    'entity_module_bundle_plugin_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    $this->installEntitySchema('entity_test_bundle_plugin');
    $this->installConfig('entity_module_bundle_plugin_test');
  }

  /**
   * Tests the bundle plugins.
   */
  public function testPluginBundles() {
    $bundled_entity_types = entity_get_bundle_plugin_entity_types();
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $bundled_entity_types['entity_test_bundle_plugin'];
    $this->assertEquals('entity_test_bundle_plugin', $entity_type->id());
    $this->assertTrue($entity_type->hasHandlerClass('bundle_plugin'));

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info */
    $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundle_info = $entity_type_bundle_info->getBundleInfo('entity_test_bundle_plugin');
    $this->assertEquals(2, count($bundle_info));
    $this->assertArrayHasKey('first', $bundle_info);
    $this->assertArrayHasKey('second', $bundle_info);

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions('entity_test_bundle_plugin');
    $this->assertArrayHasKey('first_mail', $field_storage_definitions);
    $this->assertArrayHasKey('second_mail', $field_storage_definitions);
    $first_field_definitions = $entity_field_manager->getFieldDefinitions('entity_test_bundle_plugin', 'first');
    $this->assertArrayHasKey('first_mail', $first_field_definitions);
    $this->assertArrayNotHasKey('second_mail', $first_field_definitions);
    $second_field_definitions = $entity_field_manager->getFieldDefinitions('entity_test_bundle_plugin', 'second');
    $this->assertArrayNotHasKey('first_mail', $second_field_definitions);
    $this->assertArrayHasKey('second_mail', $second_field_definitions);

    $first_entity = EntityTestBundlePlugin::create([
      'type' => 'first',
      'first_mail' => 'admin@test.com',
    ]);
    $first_entity->save();
    $first_entity = EntityTestBundlePlugin::load($first_entity->id());
    $this->assertEquals('admin@test.com', $first_entity->first_mail->value);

    $second_entity = EntityTestBundlePlugin::create([
      'type' => 'second',
      'second_mail' => 'admin@example.com',
    ]);
    $second_entity->save();
    $second_entity = EntityTestBundlePlugin::load($second_entity->id());
    $this->assertEquals('admin@example.com', $second_entity->second_mail->value);
  }

}
