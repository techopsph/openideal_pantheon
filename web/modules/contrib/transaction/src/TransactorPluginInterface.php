<?php

namespace Drupal\transaction;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for transactor plugins.
 */
interface TransactorPluginInterface extends PluginFormInterface, ConfigurableInterface, DependentPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Generic result code for successful execution.
   */
  const RESULT_OK = 1;

  /**
   * Generic result code for failed execution.
   */
  const RESULT_ERROR = -1;

  /**
   * Executes a transaction.
   *
   * By calling this method, the transactor will set the result code in the
   * transaction.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The transaction to execute.
   * @param \Drupal\transaction\TransactionInterface $last_executed
   *   The last executed transaction with the same type and target. Empty if
   *   this is the first one.
   *
   * @return bool
   *   TRUE if transaction was executed, FALSE otherwise.
   *
   * @see \Drupal\transaction\TransactionInterface::getResultCode()
   */
  public function executeTransaction(TransactionInterface $transaction, TransactionInterface $last_executed = NULL);

  /**
   * Compose a message that describes the execution result of a transaction.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The executed transaction for which to compose the result message.
   * @param string $langcode
   *   (optional) The language to use in message composition. Defaults to the
   *   current content language.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translatable markup with the execution result message, FALSE if
   *   transaction execution was never called.
   */
  public function getResultMessage(TransactionInterface $transaction, $langcode = NULL);

  /**
   * Compose a human readable description for the given transaction.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The transaction to describe.
   * @param string $langcode
   *   (optional) For which language the transaction description should be
   *   composed, defaults to the current content language.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   A string or translatable markup with the generated description.
   */
  public function getTransactionDescription(TransactionInterface $transaction, $langcode = NULL);

  /**
   * Compose human readable details for the given transaction.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The transaction to detail.
   * @param string $langcode
   *   (optional) For which language the transaction details should be
   *   composed, defaults to the current content language.
   *
   * @return array
   *   An array of strings and/or translatable markup objects representing each
   *   one a line detailing the transaction. Empty array if no details
   *   generated.
   */
  public function getTransactionDetails(TransactionInterface $transaction, $langcode = NULL);

  /**
   * Compose a messsage with execution indications for the given transaction.
   *
   * This message is commonly shown to the users upon transaction execution.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The pending transaction to compose indications about.
   * @param string $langcode
   *   (optional) For which language the execution indications should be
   *   composed, defaults to the current content language.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   A string or translatable markup with the generated message.
   */
  public function getExecutionIndications(TransactionInterface $transaction, $langcode = NULL);

  /**
   * Check if the transactor is applicable to a particular entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to check.
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   (optional) Restrict the checking to a particular transaction type.
   *
   * @return bool
   *   TRUE if transactor is applicable to the given entity.
   */
  public function isApplicable(ContentEntityInterface $entity, TransactionTypeInterface $transaction_type = NULL);

}
