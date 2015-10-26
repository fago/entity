<?php

/**
 * @file
 * Contains \Drupal\Tests\Unit\RouteEnhancer\EntityRevisionRouteEnhancerTest.
 */

namespace Drupal\Tests\Unit\RouteEnhancer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity\RouteEnhancer\EntityRevisionRouteEnhancer;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\entity\RouteEnhancer\EntityRevisionRouteEnhancer
 * @group entity
 */
class EntityRevisionRouteEnhancerTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \Drupal\entity\RouteEnhancer\EntityRevisionRouteEnhancer
   */
  protected $routeEnhancer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->routeEnhancer = new EntityRevisionRouteEnhancer();
  }

  /**
   * @covers ::applies
   */
  public function testAppliesWithNoParameters() {
    $route = new Route('/test-path');

    $this->assertFalse($this->routeEnhancer->applies($route));
  }

  /**
   * @covers ::applies
   */
  public function testAppliesWithNoneRevisionParameters() {
    $route = new Route('/test-path/{entity_test}', [], [], ['parameters' => ['entity_test' => ['type' => 'entity:entity_test']]]);

    $this->assertFalse($this->routeEnhancer->applies($route));
  }

  /**
   * @covers ::applies
   */
  public function testAppliesWithRevisionParameters() {
    $route = new Route('/test-path/{entity_test_revision}', [], [], ['parameters' => ['entity_test_revision' => ['type' => 'entity_revision:entity_test']]]);

    $this->assertTrue($this->routeEnhancer->applies($route));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceWithoutEntityRevision() {
    $route = new Route('/test-path/{entity_test}', [], [], ['parameters' => ['entity_test' => ['type' => 'entity:entity_test']]]);
    $request = Request::create('/test-path/123');
    $entity = $this->prophesize(EntityInterface::class);

    $defaults = [];
    $defaults['entity_test'] = $entity->reveal();
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $this->assertEquals($defaults, $this->routeEnhancer->enhance($defaults, $request));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceWithEntityRevision() {
    $route = new Route('/test-path/{entity_test_revision}', [], [], ['parameters' => ['entity_test_revision' => ['type' => 'entity_revision:entity_test']]]);
    $request = Request::create('/test-path/123');
    $entity = $this->prophesize(EntityInterface::class);

    $defaults = [];
    $defaults['entity_test_revision'] = $entity->reveal();
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;

    $expected = $defaults;
    $expected['_entity_revision'] = $defaults['entity_test_revision'];
    $this->assertEquals($expected, $this->routeEnhancer->enhance($defaults, $request));
  }

}
