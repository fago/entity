<?php

namespace Drupal\entity\Query;

use Drupal\Core\Database\Query\Select;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Database\Query\Condition as SqlCondition;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SqlQueryAlter {

  /** @var  \Drupal\Core\Entity\EntityFieldManagerInterface */
  protected $entityFieldManager;

  /** @var \Drupal\Core\Session\AccountInterface */
  protected $currentUser;

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;

  /**
   * SqlQueryAlter constructor.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser) {
    $this->entityFieldManager = $entityFieldManager;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'), $container->get('entity_type.manager'), $container->get('current_user')
    );
  }

  /**
   * Adds the query_access condition for any SQL query.
   *
   * This is shared logic between altering entity queries and views queries.
   *
   * @param \Drupal\Core\Database\Query\Select $query
   *   The select query we alter.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type we deal with.
   */
  public function queryAlter(Select $query, EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }
    $table_mapping = $storage->getTableMapping();

    if ($entity_type->hasHandlerClass('query_access')) {
      /** @var \Drupal\entity\Query\QueryAccessHandlerInterface $query_access */
      $query_access = $this->entityTypeManager->getHandler($entity_type_id, 'query_access');
      $condition = $query_access->conditions('view', $this->currentUser);

      $sql_condition = $query->andConditionGroup();
      if (count($condition)) {
        $sql_condition = $this->applyCondition($entity_type, $table_mapping, $query, $sql_condition, $condition);

        $query->condition($sql_condition);
      }
    }
  }

  /**
   * Tries to ensure that a given table exists.
   *
   * @return string
   *   The table name/alias used in the query.
   */
  protected static function ensureTable(Select $select, $table, EntityTypeInterface $entity_type) {
    $tables = $select->getTables();
    foreach ($tables as $table_info) {
      if (isset($table_info['table']) && $table_info['table'] === $table) {
        return $table_info['alias'];
      }
    }

    // @todo Is this the right join?
    return $select->innerJoin($table, NULL, $entity_type->getBaseTable() . '.' . $entity_type->getKey('id') . ' = ' . '%alias.entity_id');
  }

  /**
   * Apply the entity conditions recursively to the sql condition.
   *
   * @return \Drupal\Core\Database\Query\Condition
   */
  protected function applyCondition(EntityTypeInterface $entity_type, DefaultTableMapping $table_mapping, Select $select, SqlCondition $sql_condition, Condition $condition) {
    $entity_type_id = $entity_type->id();
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);

    foreach ($condition->conditions() as $cond) {
      // Support nested conditions.
      if ($cond['field'] instanceof Condition) {
        $sql_condition->condition($this->applyCondition($entity_type, $table_mapping, $select, $sql_condition->conditionGroupFactory($cond['field']->getConjunction()), $cond['field']));
      }
      else {
        $field_storage_definition = $field_storage_definitions[$cond['field']];

        // Determine the real table and column before applying the condition.
        if ($field_storage_definition->isBaseField()) {
          $table_name = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
        }
        else {
          $table_name = $table_mapping->getDedicatedDataTableName($field_storage_definition);
        }

        $table_name = static::ensureTable($select, $table_name, $entity_type);

        // @todo we need support for non main properties?
        // @todo we need support for revision queries?
        // @todo What do we do with the langcode value?
  
        $field_column_name = $table_mapping->getFieldColumnName($field_storage_definition, $field_storage_definition->getMainPropertyName());

        $sql_condition->condition("{$table_name}.{$field_column_name}", $cond['value'], $cond['operator']);
      }
    }
    return $sql_condition;
  }

}
