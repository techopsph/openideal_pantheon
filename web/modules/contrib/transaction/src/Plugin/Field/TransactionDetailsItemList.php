<?php

namespace Drupal\transaction\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * Transaction details field type item list.
 */
class TransactionDetailsItemList extends FieldItemList {

  /**
   * Whether or not the values have been calculated.
   *
   * @var bool
   */
  protected $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensureCalculated();
    return new \ArrayIterator($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $this->ensureCalculated();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureCalculated();
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
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      foreach ($this->getEntity()->getDetails(TRUE) as $delta => $value) {
        $this->list[$delta] = $this->createItem($delta, $value);
      }
      $this->isCalculated = TRUE;
    }
  }

}
