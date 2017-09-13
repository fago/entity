<?php

namespace Drupal\entity\Query;

use Drupal\Core\Database\Query\Select;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Database\Query\Condition as SqlCondition;

class SqlQueryAlter {

  /** @var  \Drupal\Core\Entity\EntityFieldManagerInterface */
  protected $entityFieldManager;

  /**
   * SqlQueryAlter constructor.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  protected static function ensureTable(Select $select, $table, EntityTypeInterface $entity_type) {
    $tables = $select->getTables();
    foreach ($tables as $table_info) {
      if (isset($table_info['table']) && $table_info['table'] === $table) {
        return $table_info['alias'];
      }
    }

    return $select->innerJoin($table, NULL, $entity_type->getBaseTable() . '.' . $entity_type->getKey('id') . ' = ' . '%alias.entity_id');
  }

  public function applyCondition(EntityTypeInterface $entity_type, DefaultTableMapping $table_mapping, Select $select, SqlCondition $sql_condition, Condition $condition) {
    $entity_type_id = $entity_type->id();
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);

    foreach ($condition->conditions() as $cond) {
      if ($cond['field'] instanceof Condition) {
        $sql_condition->condition($this->applyCondition($entity_type, $table_mapping, $select, $sql_condition->conditionGroupFactory($cond['field']->getConjunction()), $cond['field']));
      }
      else {
        $field_storage_definition = $field_storage_definitions[$cond['field']];
        if ($field_storage_definition->isBaseField()) {
          $table_name = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
        }
        else {
          $table_name = $table_mapping->getDedicatedDataTableName($field_storage_definition);
        }

        $table_name = static::ensureTable($select, $table_name, $entity_type);

        // @todo we need support for non main properties?
        // @todo we need support for revision queries?
        $field_name = $table_mapping->getFieldColumnName($field_storage_definition, $field_storage_definition->getMainPropertyName());

        $sql_condition->condition("{$table_name}.{$field_name}", $cond['value'], $cond['operator']);
      }
    }
    return $sql_condition;
  }

}
