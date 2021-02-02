<?php

namespace Drupal\transaction;

/**
 * Transaction service interface.
 */
interface TransactionServiceInterface {

  /**
   * Gets the last executed transaction for a given type and target entity.
   *
   * @param string|\Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity object or ID.
   * @param string|\Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The transaction type object or ID.
   *
   * @return null|\Drupal\transaction\TransactionInterface
   *   The last executed transaction, NULL if not found.
   *
   * @throws \InvalidArgumentException
   *   If the given transaction type ID does not exists.
   */
  public function getLastExecutedTransaction($target_entity, $transaction_type);

}
