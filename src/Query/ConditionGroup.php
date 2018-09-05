<?php

namespace Drupal\entity\Query;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Represents a group of access conditions.
 *
 * Used by query access handlers for filtering lists of entities based on
 * granted permissions.
 *
 * Examples:
 * @code
 *   // Filter by node type and uid.
 *   $condition_group = new ConditionGroup();
 *   $condition_group->addCondition('type', ['article', 'page']);
 *   $condition_group->addCondition('uid', '1');
 *
 *   // Filter by node type or status.
 *   $condition_group = new ConditionGroup('OR');
 *   $condition_group->addCondition('type', ['article', 'page']);
 *   $condition_group->addCondition('status', '1', '<>');
 *
 *   // Nested condition groups: node type AND (uid OR status).
 *   $condition_group = new ConditionGroup();
 *   $condition_group->addCondition('type', ['article', 'page']);
 *   $condition_group->addCondition((new ConditionGroup('OR'))
 *     ->addCondition('uid', 1)
 *     ->addCondition('status', '1')
 *   );
 * @endcode
 */
final class ConditionGroup implements \Countable, CacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The conditions.
   *
   * @var \Drupal\entity\Query\Condition[]|\Drupal\entity\Query\ConditionGroup[]
   */
  protected $conditions = [];

  /**
   * The conjunction.
   *
   * @var string
   */
  protected $conjunction;

  /**
   * Constructs a new ConditionGroup object.
   *
   * @param string $conjunction
   *   The conjunction.
   */
  public function __construct($conjunction = 'AND') {
    $this->conjunction = $conjunction;
  }

  /**
   * Gets the conjunction.
   *
   * @return string
   *   The conjunction. Possible values: AND, OR.
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * Adds a condition.
   *
   * @param string|\Drupal\entity\Query\ConditionGroup $field
   *   Either a condition group (for nested AND/OR conditions), or a
   *   field name with an optional column name. E.g: 'uid', 'address.locality'.
   * @param mixed $value
   *   The value.
   * @param string $operator
   *   The operator.
   *   Possible values: =, <>, <, <=, >, >=, BETWEEN, NOT BETWEEN,
   *                   IN, NOT IN, IS NULL, IS NOT NULL.
   *
   * @return $this
   */
  public function addCondition($field, $value = NULL, $operator = NULL) {
    if ($field instanceof ConditionGroup) {
      if ($field->count() === 1) {
        // The condition group only has a single condition, merge it.
        $this->conditions[] = reset($field->getConditions());
        $this->addCacheTags($field->getCacheTags());
        $this->addCacheContexts($field->getCacheContexts());
        $this->mergeCacheMaxAge($field->getCacheMaxAge());
      }
      elseif ($field->count() > 1) {
        $this->conditions[] = $field;
      }
    }
    else {
      $this->conditions[] = new Condition($field, $value, $operator);
    }

    return $this;
  }

  /**
   * Gets all conditions and nested condition groups.
   *
   * @return \Drupal\entity\Query\Condition[]|\Drupal\entity\Query\ConditionGroup[]
   *   The conditions, where each one is either a Condition or a nested
   *   ConditionGroup. Returned by reference, to allow callers to replace
   *   or remove conditions.
   */
  public function &getConditions() {
    return $this->conditions;
  }

  /**
   * Clones the contained conditions when the condition group is cloned.
   */
  public function __clone() {
    foreach ($this->conditions as $i => $condition) {
      $this->conditions[$i] = clone $condition;
    }
  }

  /**
   * Gets the string representation of the condition group.
   *
   * @return string
   *   The string representation of the condition group.
   */
  public function __toString() {
    // Special case for a single, nested condition group:
    if (count($this->conditions) == 1) {
      return (string) reset($this->conditions);
    }
    $lines = [];
    foreach ($this->conditions as $condition) {
      $lines[] = str_replace("\n", "\n  ", (string) $condition);
    }
    return $lines ? "(\n  " . implode("\n    {$this->conjunction}\n  ", $lines) . "\n)" : '';
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = $this->cacheTags;
    foreach ($this->conditions as $condition) {
      if ($condition instanceof ConditionGroup) {
        $tags = array_merge($tags, $condition->getCacheTags());
      }
    }
    return Cache::mergeTags($tags, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = $this->cacheContexts;
    foreach ($this->conditions as $condition) {
      if ($condition instanceof ConditionGroup) {
        $cache_contexts = array_merge($cache_contexts, $condition->getCacheContexts());
      }
    }
    return Cache::mergeContexts($cache_contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $max_age = $this->cacheMaxAge;
    foreach ($this->conditions as $condition) {
      if ($condition instanceof ConditionGroup) {
        $max_age = Cache::mergeMaxAges($max_age, $condition->getCacheMaxAge());
      }
    }
    return $max_age;
  }

}
