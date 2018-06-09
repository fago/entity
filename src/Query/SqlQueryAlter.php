<?php

namespace Drupal\entity\Query;

use Drupal\Core\Cache\CacheableMetadata;
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

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new SqlQueryAlter object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
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
      $condition = $query_access->buildConditions('view', $this->currentUser);

      if (count($condition)) {
        $sql_condition = $query->conditionGroupFactory($condition->getConjunction());
        $sql_condition = $this->applyCondition($entity_type, $table_mapping, $query, $sql_condition, $condition);
        $query->condition($sql_condition);
      }

      $this->applyCacheability(CacheableMetadata::createFromObject($condition));
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
   * Apply the entity conditions recursively to the SQL condition.
   *
   * @return \Drupal\Core\Database\Query\Condition
   *   The modified SQL condition.
   */
  protected function applyCondition(EntityTypeInterface $entity_type, DefaultTableMapping $table_mapping, Select $select, SqlCondition $sql_condition, ConditionGroup $condition) {
    $entity_type_id = $entity_type->id();
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);

    foreach ($condition->getConditions() as $cond) {
      // Support nested conditions.
      if ($cond instanceof ConditionGroup) {
        $sql_condition->condition($this->applyCondition($entity_type, $table_mapping, $select, $sql_condition->conditionGroupFactory($cond->getConjunction()), $cond));
      }
      else {
        $field_storage_definition = $field_storage_definitions[$cond->getField()];

        // Determine the real table and column before applying the condition.
        if ($field_storage_definition->isBaseField()) {
          $table_name = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
        }
        else {
          $table_name = $table_mapping->getDedicatedDataTableName($field_storage_definition);
        }

        $table_alias = static::ensureTable($select, $table_name, $entity_type);

        // @todo we need support for non main properties?
        // @todo we need support for revision queries?

        $field_column_name = $table_mapping->getFieldColumnName($field_storage_definition, $field_storage_definition->getMainPropertyName());

        $sql_condition->condition("{$table_alias}.{$field_column_name}", $cond->getValue(), $cond->getOperator());
      }
    }

    return $sql_condition;
  }

  /**
   * Applies the cacheablity metadata to the current request.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   The cacheability metadata.
   */
  protected function applyCacheability(CacheableMetadata $cacheable_metadata) {
    $request = \Drupal::requestStack()->getCurrentRequest();
    $renderer = \Drupal::service('renderer');
    if ($request->isMethodCacheable() && $renderer->hasRenderContext()) {
      $build = [];
      $cacheable_metadata->applyTo($build);
      $renderer->render($build);
    }
  }

}
