<?php

/**
 * @file
 * Contains \Drupal\entity\EntityKeysFieldsTrait.
 */

namespace Drupal\entity;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides base fields for entity keys.
 */
trait EntityKeysFieldsTrait {

  /**
   * Returns some base field definitions.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected function entityKeysBaseFieldDefinitions(ContentEntityTypeInterface $entity_type) {
    $fields = [];

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    if ($entity_type->isRevisionable()) {
      $fields[$entity_type->getKey('revision')] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Revision ID'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }

    $fields[$entity_type->getKey('langcode')] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 2,
      ]);

    if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      $bundle_key = $entity_type->getKey('bundle');
      $fields[$bundle_key] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Type'))
        ->setSetting('target_type', $bundle_entity_type_id)
        ->setReadOnly(TRUE);
    }

    return $fields;
  }

}
