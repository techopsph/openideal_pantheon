<?php

namespace Drupal\transaction\Plugin\Transaction;

use Drupal\transaction\TransactionInterface;

/**
 * Transactor for accounting type transactions.
 *
 * @Transactor(
 *   id = "transaction_balance",
 *   title = @Translation("Balance"),
 *   description = @Translation("Transactor for accounting type transactions."),
 *   transaction_fields = {
 *     {
 *       "name" = "amount",
 *       "type" = "decimal",
 *       "title" = @Translation("Amount"),
 *       "description" = @Translation("A numeric field with the amount of the transaction."),
 *       "required" = TRUE,
 *       "list" = TRUE,
 *     },
 *     {
 *       "name" = "balance",
 *       "type" = "decimal",
 *       "title" = @Translation("Balance"),
 *       "description" = @Translation("A numeric field to store the current balance."),
 *       "required" = TRUE,
 *       "list" = TRUE,
 *     },
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
 *     {
 *       "name" = "target_balance",
 *       "type" = "decimal",
 *       "title" = @Translation("Balance"),
 *       "description" = @Translation("A numeric field to update with the current balance."),
 *       "required" = FALSE,
 *     },
 *   },
 * )
 */
class BalanceTransactor extends GenericTransactor {

  /**
   * {@inheritdoc}
   */
  public function executeTransaction(TransactionInterface $transaction, TransactionInterface $last_executed = NULL) {
    if (!parent::executeTransaction($transaction, $last_executed)) {
      return FALSE;
    }

    $settings = $transaction->getType()->getPluginSettings();

    // Current balance from the last executed transaction. The current
    // transaction balance will take as the initial balance.
    $balance = $last_executed ? $last_executed->get($settings['balance'])->value : $transaction->get($settings['balance'])->value;
    // Transaction amount.
    $amount = $transaction->get($settings['amount'])->value;
    // Set result into transaction balance.
    $result = $balance + $amount;
    $transaction->get($settings['balance'])->setValue($result);

    // Reflect balance on the target entity.
    $target_entity = $transaction->getTargetEntity();
    if (isset($settings['target_balance'])
      && $target_entity->hasField($settings['target_balance'])) {
      $target_entity->get($settings['target_balance'])->setValue($result);
      // Set the property indicating that the target entity was updated on
      // execution.
      $transaction->setProperty(TransactionInterface::PROPERTY_TARGET_ENTITY_UPDATED, TRUE);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionDescription(TransactionInterface $transaction, $langcode = NULL) {
    if ($transaction->isNew()) {
      return parent::getTransactionDescription($transaction, $langcode);
    }

    $settings = $transaction->getType()->getPluginSettings();

    // Transaction amount.
    $amount = $transaction->get($settings['amount'])->value;

    $t_options = $langcode ? ['langcode' => $langcode] : [];
    $t_args = ['@status' => $transaction->isPending() ? $this->t('(pending)') : ''];
    if ($amount > 0) {
      $description = $this->t('Credit transaction @status', $t_args, $t_options);
    }
    elseif ($amount < 0) {
      $description = $this->t('Debit transaction @status', $t_args, $t_options);
    }
    else {
      $description = $this->t('Zero amount transaction @status', $t_args, $t_options);
    }

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionIndications(TransactionInterface $transaction, $langcode = NULL) {
    $settings = $transaction->getType()->getPluginSettings();
    // Transaction amount.
    $amount = $transaction->get($settings['amount'])->value;

    // @todo pretty print of amount according to default display settings
    $t_args = ['@amount' => $transaction->get($settings['amount'])->getString()];
    $t_options = $langcode ? ['langcode' => $langcode] : [];
    if ($amount > 0) {
      $indication = $this->t('The current balance will increase by @amount.', $t_args, $t_options);
    }
    elseif ($amount < 0) {
      $indication = $this->t('The current balance will decrease by @amount.', $t_args, $t_options);
    }
    else {
      $indication = $this->t('The current balance will not be altered.', [], $t_options);
    }

    return $indication . ' ' . parent::getExecutionIndications($transaction, $langcode);
  }

}
