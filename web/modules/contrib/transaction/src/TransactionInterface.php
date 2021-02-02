<?php

namespace Drupal\transaction;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * The interface for transaction entities.
 */
interface TransactionInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Transaction executed state.
   */
  const EXECUTED = 1;

  /**
   * Transaction pending state.
   */
  const PENDING = 0;

  /**
   * Generic result code for successful execution.
   *
   * @deprecated in transaction:8.x-1.0-alpha3 and is removed from
   *   transaction:8.x-1.0-beta1. Use instead defined const in
   *   \Drupal\transaction\TransactorPluginInterface and derived transactors.
   */
  const RESULT_OK = 1;

  /**
   * Generic result code for failed execution.
   *
   * @deprecated in transaction:8.x-1.0-alpha3 and is removed from
   *   transaction:8.x-1.0-beta1. Use instead defined const in
   *   \Drupal\transaction\TransactorPluginInterface and derived transactors.
   */
  const RESULT_ERROR = -1;

  /**
   * Name of property indicating that the target entity was updated.
   *
   * @var string
   */
  const PROPERTY_TARGET_ENTITY_UPDATED = 'transaction_target_entity_updated';

  /**
   * Returns the transaction type ID.
   *
   * @return string
   *   The transaction type.
   */
  public function getTypeId();

  /**
   * Returns the transaction type.
   *
   * @return \Drupal\transaction\TransactionTypeInterface
   *   The transaction type.
   */
  public function getType();

  /**
   * Gets the transaction creation timestamp.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the transaction creation timestamp.
   *
   * @param int $timestamp
   *   The subscription creation timestamp.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get the previous same-type transaction in order of execution.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The previously executed transaction. NULL if this is the first one.
   *
   * @throws \Drupal\transaction\InvalidTransactionStateException
   *   If the transaction was not executed yet.
   */
  public function getPrevious();

  /**
   * Get the next same-type transaction in order of execution.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The previously executed transaction. NULL if this is the last executed.
   *
   * @throws \Drupal\transaction\InvalidTransactionStateException
   *   If the transaction was not executed yet.
   */
  public function getNext();

  /**
   * Get the transaction target entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The transaction's target entity.
   */
  public function getTargetEntity();

  /**
   * Get the transaction target entity ID.
   *
   * @return int
   *   The transaction's target entity ID.
   */
  public function getTargetEntityId();

  /**
   * Sets the transaction's target entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The target content entity.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction entity.
   *
   * @throws \InvalidArgumentException
   *   If the type of the given entity is not valid for the transaction type.
   */
  public function setTargetEntity(ContentEntityInterface $entity);

  /**
   * Gets the transaction operation.
   *
   * @return \Drupal\transaction\TransactionOperationInterface
   *   The transaction operation for this transaction. NULL if not set.
   */
  public function getOperation();

  /**
   * Gets the transaction operation ID.
   *
   * @return string
   *   The transaction operation for this transaction. NULL if not set.
   */
  public function getOperationId();

  /**
   * Sets the transaction operation.
   *
   * @param string|\Drupal\transaction\TransactionOperationInterface $operation
   *   (optional) The transaction operation config entity or its ID. Defaults
   *     to NULL that clears the current value.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction.
   */
  public function setOperation($operation = NULL);

  /**
   * Returns the transaction description.
   *
   * Transactors compose the description based on the transaction data.
   *
   * @param bool $reset
   *   Forces to recompose the transaction description.
   *
   * @return string
   *   The transaction description.
   */
  public function getDescription($reset = FALSE);

  /**
   * Returns the transaction details.
   *
   * Transactors compose datails based on the transaction data.
   *
   * @param bool $reset
   *   Forces to recompose the transaction details.
   *
   * @return string[]
   *   An array with details.
   */
  public function getDetails($reset = FALSE);

  /**
   * Returns a property value.
   *
   * @param string $key
   *   Property key to get.
   *
   * @return string
   *   The value of the given key, NULL if not set.
   */
  public function getProperty($key);

  /**
   * Sets a property value.
   *
   * @param string $key
   *   Property key.
   * @param string $value
   *   Value to store. NULL will delete the property.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction.
   */
  public function setProperty($key, $value = NULL);

  /**
   * Get a keyed array with all the transaction properties.
   *
   * @return array
   *   Array with all defined properties. Empty array if no one defined.
   */
  public function getProperties();

  /**
   * Executes the transaction.
   *
   * The execution result code will be set or changed.
   *
   * @param bool $save
   *   (optional) Save the transaction after succeeded execution.
   * @param \Drupal\User\UserInterface $executor
   *   (optional) The user that executes the transaction. Current user by
   *   default.
   *
   * @return bool
   *   TRUE if transaction was executed, FALSE otherwise.
   *
   * @throws \Drupal\transaction\InvalidTransactionStateException
   *   If the transaction is already executed.
   *
   * @see \Drupal\transaction\TransactionInterface::getResultCode()
   */
  public function execute($save = TRUE, UserInterface $executor = NULL);

  /**
   * Gets the execution result code.
   *
   * Positive integer means that the execution was successful:
   *  - 1: generic code for successful execution, @see
   *       \Drupal\transaction\TransactorPluginInterface::RESULT_OK
   *  - > 1: transactor specific successful execution result code.
   *
   * Negative integer means that there were errors trying to execute the
   * transaction:
   *  - -1: generic code for failed execution, @see
   *        \Drupal\transaction\TransactorPluginInterface::RESULT_ERROR
   *  - < -1: transactor specific failed execution result code
   *
   * Errors can be recoverable or fatal. If transaction is still pending
   * execution, there was a recoverable error and the execution can be retried.
   * If transaction is in executed state, there was a fatal error and the
   * execution cannot be retried.
   *
   * @return int
   *   The result code. FALSE if transaction execution was never called.
   */
  public function getResultCode();

  /**
   * Sets the execution result code.
   *
   * @param int $code
   *   The result code.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction.
   *
   * @see \Drupal\transaction\TransactionInterface::getResultCode()
   */
  public function setResultCode($code);

  /**
   * Gets the execution result message.
   *
   * Transactors compose the result message based on the result code.
   *
   * @param bool $reset
   *   Forces to recompose the result message.
   *
   * @return string
   *   The execution result message. FALSE if transaction execution was never
   *   called.
   */
  public function getResultMessage($reset = FALSE);

  /**
   * Gets the transaction execution sequence.
   *
   * @return int|null
   *   The execution sequence number, NULL for non-executed transactions.
   */
  public function getExecutionSequence();

  /**
   * Sets the execution sequence number.
   *
   * @param int|null $execution_sequence
   *   The execution sequence number, NULL to unset the existing value.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction.
   *
   * @throws \InvalidArgumentException
   *   When the sequence number is lower than 1.
   */
  public function setExecutionSequence($execution_sequence);

  /**
   * Gets the transaction execution timestamp.
   *
   * @return int
   *   The execution timestamp. NULL if transaction was no executed.
   */
  public function getExecutionTime();

  /**
   * Sets the transaction execution timestamp.
   *
   * @param int $timestamp
   *   The execution timestamp.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction.
   */
  public function setExecutionTime($timestamp);

  /**
   * Gets the ID of the user that executed the transaction.
   *
   * @return int
   *   The user ID of the executor. FALSE if transaction was no executed.
   */
  public function getExecutorId();

  /**
   * Gets the user that executed the transaction.
   *
   * @return \Drupal\User\UserInterface
   *   The executor user. NULL if transaction was no executed.
   */
  public function getExecutor();

  /**
   * Sets the user that executed the transaction.
   *
   * @param \Drupal\User\UserInterface $user
   *   The executor user.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction.
   */
  public function setExecutor(UserInterface $user);

  /**
   * Indicates if the transaction is pending execution.
   *
   * @return bool
   *   TRUE on pending execution, FALSE if execution was done successfully.
   */
  public function isPending();

}
