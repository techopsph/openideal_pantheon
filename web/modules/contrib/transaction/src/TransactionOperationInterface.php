<?php

namespace Drupal\transaction;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a transaction operation.
 */
interface TransactionOperationInterface extends ConfigEntityInterface {

  /**
   * Gets the transaction type ID to which the operation belongs.
   *
   * @return string
   *   The transaction type ID.
   */
  public function getTransactionTypeId();

  /**
   * Gets the operation description template.
   *
   * @return string
   *   The description. Empty string if not set.
   */
  public function getDescription();

  /**
   * Sets the operation description template.
   *
   * @param string $description
   *   (optional) The new description. Empty string if none given.
   *
   * @return \Drupal\transaction\TransactionOperationInterface
   *   The called transaction operation.
   */
  public function setDescription($description = '');

  /**
   * Gets the current detail templates.
   *
   * @return string[]
   *   Array with the detail templates. Empty array if none set.
   */
  public function getDetails();

  /**
   * Sets the operation detail templates.
   *
   * @param string[] $details
   *   (optional) Array with the new detail templates. Empty array by default.
   *
   * @return \Drupal\transaction\TransactionOperationInterface
   *   The called transaction operation.
   */
  public function setDetails(array $details = []);

  /**
   * Adds a new detail template line.
   *
   * @param string $detail
   *   The new template detail.
   *
   * @return \Drupal\transaction\TransactionOperationInterface
   *   The called transaction operation.
   */
  public function addDetail($detail);

}
