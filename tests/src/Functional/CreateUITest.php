<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Functional\CreateUITest.
 */

namespace Drupal\Tests\entity\Functional;

use Drupal\entity_module_test\Entity\EnhancedEntity;
use Drupal\entity_module_test\Entity\EnhancedEntityBundle;
use Drupal\simpletest\BrowserTestBase;

/**
 * Tests the entity creation UI provided by EntityCreateController.
 *
 * @group entity
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CreateUITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_module_test', 'user', 'entity'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    EnhancedEntityBundle::create([
      'id' => 'first',
      'label' => 'First',
      'description' => 'The first bundle',
    ])->save();
    $account = $this->drupalCreateUser(['administer entity_test_enhanced']);
    $this->drupalLogin($account);
  }

  /**
   * Tests the add page.
   */
  public function testAddPage() {
    // This test revealed that if the first bundle gets created in testAddPage
    // before drupalGet(), the built cache won't get reset when the second
    // bundle is created. This is a BrowserTestBase specific bug.
    // @todo Remove comment when the bug is fixed.

    // When only one bundle exists, the add page should redirect to the form.
    $this->drupalGet('/entity_test_enhanced/add');
    $this->assertSession()->addressEquals('/entity_test_enhanced/add/first');

    EnhancedEntityBundle::create([
      'id' => 'second',
      'label' => 'Second',
      'description' => 'The <b>second</b> bundle',
    ])->save();
    $this->drupalGet('/entity_test_enhanced/add');
    $assert = $this->assertSession();
    $assert->addressEquals('/entity_test_enhanced/add');
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', '.page-title', 'Add entity test with enhancements');
    // Confirm the presence of unescaped descriptions.
    $assert->responseContains('The first bundle');
    $assert->responseContains('The <b>second</b> bundle');
    // Validate the links.
    $link = $this->getSession()->getPage()->findLink('First');
    $this->assertEquals('/entity_test_enhanced/add/first', $link->getAttribute('href'));
    $link = $this->getSession()->getPage()->findLink('Second');
    $this->assertEquals('/entity_test_enhanced/add/second', $link->getAttribute('href'));
  }

  /**
   * Tests the add form.
   */
  public function testAddForm() {
    $this->drupalGet('/entity_test_enhanced/add/first');
    $assert = $this->assertSession();
    $assert->elementTextContains('css', '.page-title', 'Add entity test with enhancements');
    $assert->elementExists('css', 'form.entity-test-enhanced-first-add-form');

    // In case of multiple bundles, the current one is a part of the page title.
    EnhancedEntityBundle::create([
      'id' => 'second',
      'label' => 'Second',
      'description' => 'The <b>second</b> bundle',
    ])->save();
    $this->drupalGet('/entity_test_enhanced/add/first');
    $this->assertSession()->elementTextContains('css', '.page-title', 'Add First');
  }

}
