<?php

namespace Drupal\transaction;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Transaction service.
 */
class TransactionService implements TransactionServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastExecutedTransaction($target_entity, $transaction_type) {
    // Argument processing.
    $target_entity_id = is_object($target_entity) ? $target_entity->id() : $target_entity;
    if (!is_object($transaction_type)
      && !$transaction_type = $this->entityTypeManager->getStorage('transaction_type')->load($transaction_type)) {
      throw new \InvalidArgumentException('Invalid transaction type.');
    }
    $transaction_type_id = $transaction_type->id();
    $target_entity_type_id = $transaction_type->getTargetEntityTypeId();

    // Search the last executed transaction.
    $storage = $this->entityTypeManager->getStorage('transaction');
    $result = $storage->getQuery()
      ->condition('type', $transaction_type_id)
      ->condition('target_entity.target_type', $target_entity_type_id)
      ->condition('target_entity.target_id', $target_entity_id)
      ->exists('executed')
      ->range(0, 1)
      ->sort('execution_sequence', 'DESC')
      ->sort('executed', 'DESC')
      ->execute();

    return count($result) ? $storage->load(array_pop($result)) : NULL;
  }

}
