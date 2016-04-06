<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Unit\Enhancer\EntityRouteEnhancerTest.
 */

namespace Drupal\Tests\entity\Unit\Enhancer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity\Entity\Enhancer\EntityRouteEnhancer;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @group entity
 * @coversDefaultClass \Drupal\entity\Entity\Enhancer\EntityRouteEnhancer
 */
class EntityRouteEnhancerTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \Drupal\entity\Entity\Enhancer\EntityRouteEnhancer
   */
  protected $routeEnhancer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->routeEnhancer = new EntityRouteEnhancer();
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
  public function testAppliesWithEntityParameters() {
    $route = new Route('/test-path/{entity_test}', [], [], [
      'parameters' => [
        'entity_test' => [
          'type' => 'entity:entity_test',
        ]
      ]
    ]);

    $this->assertTrue($this->routeEnhancer->applies($route));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceWithoutEntity() {
    $route = new Route('/test-path/{entity_test}');
    $request = Request::create('/test-path/123');

    $defaults = [];
    $defaults['entity_test'] = 123;
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $this->assertEquals($defaults, $this->routeEnhancer->enhance($defaults, $request));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceWithEntity() {
    $route = new Route('/test-path/{entity_test}', [], [], ['parameters' => ['entity_test' => ['type' => 'entity:entity_test']]]);
    $request = Request::create('/test-path/123');
    $entity = $this->prophesize(EntityInterface::class);

    $defaults = [];
    $defaults['entity_test'] = $entity->reveal();
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;

    $expected = $defaults;
    $expected['_entity'] = $defaults['entity_test'];
    $this->assertEquals($expected, $this->routeEnhancer->enhance($defaults, $request));
  }

}
