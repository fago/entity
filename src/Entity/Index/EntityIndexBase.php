<?php

namespace Drupal\entity\Entity\Index;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

class EntityIndexBase implements EntityIndexInterface {

  /**
   * @var string
   */
  protected $collection = 'entity.index.id';

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * @var array
   */
  protected $cache = array();

  /**
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory) {
    $this->keyValueStore = $key_value_factory->get($this->collection);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $values = $this->getMultiple(array($key));
    return isset($values[$key]) ? $values[$key] : array();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\State\State::getMultiple()
   */
  public function getMultiple(array $keys) {
    $values = array();
    $load = array();
    foreach ($keys as $key) {
      // Check if we have a value in the cache.
      if (isset($this->cache[$key])) {
        $values[$key] = $this->cache[$key];
      }
      // Load the value if we don't have an explicit NULL value.
      elseif (!array_key_exists($key, $this->cache)) {
        $load[] = $key;
      }
    }

    if ($load) {
      $loaded_values = $this->keyValueStore->getMultiple($load);
      foreach ($load as $key) {
        // If we find a value, even one that is NULL, add it to the cache and
        // return it.
        if (isset($loaded_values[$key]) || array_key_exists($key, $loaded_values)) {
          $values[$key] = $loaded_values[$key];
          $this->cache[$key] = $loaded_values[$key];
        }
        else {
          $this->cache[$key] = NULL;
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function add(EntityInterface $entity) {
    $this->addMultiple(array($entity));
  }

  /**
   * {@inheritdoc}
   */
  public function addMultiple(array $entities) {
    $values = array();
    foreach ($entities as $entity) {
      $key = $this->buildKey($entity);
      $value = $this->buildValue($entity);
      $values[$key] = $value;
      $this->cache[$key] = $value;
    }
    $this->keyValueStore->setMultiple($values);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildKey(EntityInterface $entity) {
    return $entity->getEntityTypeId() . ':' . $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function buildValue(EntityInterface $entity) {
    !$is_new = $entity->isNew();

    return array(
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $is_new ? 0 : $entity->id(),
      'uuid' => $entity->uuid(),
    );
  }

}
