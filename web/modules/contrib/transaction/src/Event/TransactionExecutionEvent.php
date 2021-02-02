<?php

namespace Drupal\transaction\Event;

use Drupal\transaction\TransactionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a transaction is executed.
 *
 * @see \Drupal\transaction\TransactionInterface::execute()
 */
class TransactionExecutionEvent extends Event {

  const EVENT_NAME = 'rules_transaction_execution';

  /**
   * The involved transaction.
   *
   * @var \Drupal\transaction\TransactionInterface
   *
   * @todo set this property as protected once rules support getters
   * @see https://www.drupal.org/project/rules/issues/2762517
   */
  public $transaction;

  /**
   * Constructs the transaction execution event.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The executed transaction.
   */
  public function __construct(TransactionInterface $transaction) {
    $this->transaction = $transaction;
  }

  /**
   * Gets the executed transaction.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The executed transaction.
   */
  public function getTransaction() {
    return $this->transaction;
  }

}
