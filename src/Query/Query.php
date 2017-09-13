<?php

namespace Drupal\entity\Query;

use Drupal\Core\Entity\Query\Sql\Condition as SqlCondition;

class Query extends \Drupal\Core\Entity\Query\Sql\Query {

  protected function getEntityTypeManager() {
    return \Drupal::entityTypeManager();
  }

  protected function currentUser() {
    return \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepare() {
    /** @var \Drupal\entity\Query\QueryAccessHandlerInterface $query_access */
    $query_access = $this->getEntityTypeManager()->getHandler($this->entityTypeId, 'query_access');
    $condition = $query_access->conditions('view', $this->currentUser());

    if (count($condition)) {
      $this->condition($this->applyCondition($condition));
    }

    return parent::prepare();
  }

  protected function applyCondition(QueryCondition $condition) {
    $query_condition = new SqlCondition($condition->getConjunction(), $this);
    foreach ($condition->conditions() as $cond) {
      if ($cond['field'] instanceof QueryCondition) {
        $query_condition->condition($this->applyCondition($cond['field']));
      }
      else {
        $query_condition->condition($cond['field'], $cond['value'], $cond['operator'], $cond['langcode']);
      }
    }
    return $query_condition;
  }

}
