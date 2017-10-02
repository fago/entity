<?php

namespace Drupal\entity\Query;

/**
 * Value object to encode a condition to a query.
 *
 * Query access handlers can use them to filter entities based upon certain access rules.
 *
 * Some examples following:
 *
 * Filter by the bundle property.
 * @code
 *   $condition->condition('bundle', ['article', 'page'])
 * @endcode
 *
 * Filter by bundle property AND uid.
 * @code
 *   $condition->condition(
 *     (new Condition('AND))
 *       ->conditon('bundle', 'article')
 *       ->condition('uid', $user->id())
 *   )
 * @endcode
 *
 * Filter by bundle property OR uid.
 * @code
 *   $condition->condition(
 *     (new Condition('OR))
 *       ->conditon('bundle', 'article')
 *       ->condition('uid', $user->id())
 *   )
 * @endcode
 */
class Condition implements \Countable {

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The conjunction of this condition group. The value is one of the following:
   *
   * - AND (default)
   * - OR
   *
   * @var string
   */
  protected $conjunction;

  /**
   * Condition constructor.
   *
   * @param string $conjunction
   */
  public function __construct($conjunction = 'AND') {
    $this->conjunction = $conjunction;
  }

  /**
   * Adds a condition.
   *
   * @param string|\Drupal\Core\Entity\Query\ConditionInterface $field
   *   The condition. Either the field name (base field or configurable field)
   *   or a nested condition object.
   * @param mixed $value
   * @param string $operator
   * @param string $langcode
   *
   * @return static
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->conditions[] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
      'langcode' => $langcode,
    ];

    return $this;
  }

  /**
   * Returns all conditions which got provided.
   *
   * @return array[]
   */
  public function conditions() {
    return $this->conditions;
  }

  /**
   * Returns the conjunction, either OR or AND.
   *
   * @return string
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->conditions);
  }

}
