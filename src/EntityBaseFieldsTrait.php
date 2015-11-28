<?php

/**
 * @file
 * Contains \Drupal\entity\EntityBaseFieldsTrait.
 */

namespace Drupal\entity;

use Drupal\Core\Field\BaseFieldDefinition;

trait EntityBaseFieldsTrait {

  protected function entityBaseFieldsDefaultBaseFields() {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

}
