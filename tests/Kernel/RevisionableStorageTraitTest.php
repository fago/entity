<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Kernel\RevisionableStorageTraitTest.
 */

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity\Storage\RevisionableStorageTrait;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * @coversDefaultClass \Drupal\entity\Storage\RevisionableStorageTrait
 * @group entity
 */
class RevisionableStorageTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'user', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_rev');

    $language = ConfigurableLanguage::create([
      'id' => 'fr',
      'label' => 'French',
    ]);
    $language->save();
  }

  /**
   * @covers ::countDefaultLanguageRevisions
   */
  public function testCountDefaultLanguageRevisions() {
    $entity = EntityTestRev::create([
    ]);
    $entity->save();

    $revision1 = clone $entity;
    $revision1->setNewRevision(TRUE);
    $revision1->save();

    $revision2 = clone $entity;
    $revision2->setNewRevision(TRUE);
    $revision2->save();

    $revision1->addTranslation('fr');
    $revision1->save();

    $revision2->addTranslation('fr');
    $revision2->save();

    $example_entity_storage = new ExampleEntityStorage();
    $this->assertEquals(3, $example_entity_storage->countDefaultLanguageRevisions($entity));
  }

  /**
   * @covers ::revisionIds
   */
  public function testRevisionIds() {
    $entity = EntityTestRev::create([
    ]);
    $entity->save();

    $revision1 = clone $entity;
    $revision1->setNewRevision(TRUE);
    $revision1->save();

    $revision2 = clone $entity;
    $revision2->setNewRevision(TRUE);
    $revision2->save();

    $revision1->addTranslation('fr');
    $revision1->save();

    $revision2->addTranslation('fr');
    $revision2->save();

    $example_entity_storage = new ExampleEntityStorage();
    $ids = $example_entity_storage->revisionIds($entity);
    $this->assertEquals([$entity->getRevisionId(), $revision1->getRevisionId(), $revision2->getRevisionId()], $ids);
  }

}

class ExampleEntityStorage {

  use RevisionableStorageTrait;

  public function getQuery($conjunction = 'AND') {
    return \Drupal::entityManager()->getStorage('entity_test_rev')->getQuery($conjunction);
  }

}
