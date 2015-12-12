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
   * Returns the base field definitions for entity keys.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected static function entityKeysBaseFieldDefinitions(ContentEntityTypeInterface $entity_type) {
    $fields = [];

    if ($entity_type->hasKey('id')) {
      $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('integer')
        ->setLabel(t('ID'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }

    if ($entity_type->hasKey('uuid')) {
      $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('uuid')
        ->setLabel(t('UUID'))
        ->setReadOnly(TRUE);
    }

    if ($entity_type->hasKey('revision')) {
      $fields[$entity_type->getKey('revision')] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Revision ID'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }

    if ($entity_type->hasKey('langcode')) {
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
    }

    $bundle_entity_type_id = $entity_type->getBundleEntityType();
    if ($bundle_entity_type_id && $entity_type->hasKey('bundle')) {
      $fields[$entity_type->getKey('bundle')] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Type'))
        ->setSetting('target_type', $bundle_entity_type_id)
        ->setReadOnly(TRUE);
    }

    return $fields;
  }

}
