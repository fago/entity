<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Unit\ParamConverter\EntityRevisionParamConverterTest.
 */

namespace Drupal\Tests\entity\Unit\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity\ParamConverter\EntityRevisionParamConverter;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\entity\ParamConverter\EntityRevisionParamConverter
 * @group entity
 */
class EntityRevisionParamConverterTest extends \PHPUnit_Framework_TestCase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tested entity revision param converter.
   *
   * @var \Drupal\entity\ParamConverter\EntityRevisionParamConverter
   */
  protected $converter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->converter = new EntityRevisionParamConverter($this->prophesize(EntityTypeManagerInterface::class)->reveal());
  }

  protected function getTestRoute() {
    $route = new Route('/test/{test_revision}');
    $route->setOption('parameters', [
      'test_revision' => [
        'type' => 'entity_revision:test',
      ],
    ]);
    return $route;
  }

  /**
   * @covers ::applies
   */
  public function testNonApplyingRoute() {
    $route = new Route('/test');
    $this->assertFalse($this->converter->applies([], 'test_revision', $route));
  }

  /**
   * @covers ::applies
   */
  public function testApplyingRoute() {
    $route = $this->getTestRoute();
    $this->assertTrue($this->converter->applies($route->getOption('parameters')['test_revision'], 'test_revision', $route));
  }

  /**
   * @covers ::convert
   */
  public function testConvert() {
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->loadRevision(1)->willReturn($entity);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('test')->willReturn($storage->reveal());
    $converter = new EntityRevisionParamConverter($entity_type_manager->reveal());

    $route = $this->getTestRoute();
    $converter->convert(1, $route->getOption('parameters')['test_revision'], 'test_revision', ['test_revision' => 1]);
  }

}
