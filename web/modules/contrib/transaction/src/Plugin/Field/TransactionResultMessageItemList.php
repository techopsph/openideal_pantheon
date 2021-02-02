<?php

namespace Drupal\transaction\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * Item list for the computed field transaction execution result message.
 */
class TransactionResultMessageItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensurePopulated();
    return new \ArrayIterator($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $this->ensurePopulated();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensurePopulated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // This is a calculated read-only field.
  }

  /**
   * Calculates the value of the item list and sets it.
   */
  protected function ensurePopulated() {
    if (!isset($this->list[0])) {
      /** @var \Drupal\transaction\TransactionInterface $entity */
      $entity = $this->getEntity();
      if (!$entity->isPending()) {
        $this->list[0] = $this->createItem(0, $entity->getResultMessage(TRUE));
      }
    }
  }

}
