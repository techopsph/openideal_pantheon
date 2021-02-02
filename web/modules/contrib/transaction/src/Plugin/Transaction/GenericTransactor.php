<?php

namespace Drupal\transaction\Plugin\Transaction;

use Drupal\transaction\TransactorBase;
use Drupal\transaction\TransactionInterface;

/**
 * Provides a generic transactor.
 *
 * @Transactor(
 *   id = "transaction_generic",
 *   title = @Translation("Generic"),
 *   description = @Translation("A simple multipurpose transactor."),
 *   transaction_fields = {
 *     {
 *       "name" = "log_message",
 *       "type" = "string",
 *       "title" = @Translation("Log message"),
 *       "description" = @Translation("A log message with details about the transaction."),
 *       "required" = FALSE,
 *     },
 *   },
 *   target_entity_fields = {
 *     {
 *       "name" = "last_transaction",
 *       "type" = "entity_reference",
 *       "title" = @Translation("Last transaction"),
 *       "description" = @Translation("A reference field in the target entity type to update with a reference to the last executed transaction of this type."),
 *       "required" = FALSE,
 *     },
 *   },
 * )
 */
class GenericTransactor extends TransactorBase {

  /**
   * {@inheritdoc}
   */
  public function executeTransaction(TransactionInterface $transaction, TransactionInterface $last_executed = NULL) {
    if (!parent::executeTransaction($transaction)) {
      return FALSE;
    }

    // Update the last execute transaction reference in the target entity.
    $settings = $transaction->getType()->getPluginSettings();
    if (isset($settings['last_transaction'])
      && ($target_entity = $transaction->getTargetEntity())
      && $target_entity->hasField($settings['last_transaction'])) {
      $target_entity->get($settings['last_transaction'])->setValue($transaction);
      // Set the property indicating that the target entity was updated on
      // execution.
      $transaction->setProperty(TransactionInterface::PROPERTY_TARGET_ENTITY_UPDATED, TRUE);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionDetails(TransactionInterface $transaction, $langcode = NULL) {
    $details = parent::getTransactionDetails($transaction, $langcode);

    // Add the log message to transaction details.
    $settings = $transaction->getType()->getPluginSettings();
    if (isset($settings['log_message'])
      && $transaction->hasField($settings['log_message'])
      && !$transaction->get($settings['log_message'])->isEmpty()) {
      $details[] = $transaction->get($settings['log_message'])->getString();
    }

    return $details;
  }

}
