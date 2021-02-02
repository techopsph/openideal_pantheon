<?php

namespace Drupal\transaction;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a type of transaction.
 */
interface TransactionTypeInterface extends ConfigEntityInterface {

  /**
   * Standard post-save execution.
   */
  const EXECUTION_STANDARD = 0;

  /**
   * Immediate execution.
   */
  const EXECUTION_IMMEDIATE = 1;

  /**
   * Scheduled execution.
   */
  const EXECUTION_SCHEDULED = 2;

  /**
   * Ask for execution on save.
   */
  const EXECUTION_ASK = 3;

  /**
   * Returns the target entity type ID of this transaction.
   *
   * @return string
   *   The target entity type ID.
   */
  public function getTargetEntityTypeId();

  /**
   * Sets the target entity type ID of this transaction.
   *
   * @param string $entity_type_id
   *   The target entity type ID.
   *
   * @return \Drupal\transaction\TransactionTypeInterface
   *   The called transaction type.
   *
   * @deprecated in transaction:8.x-1.0 and is removed from transaction:8.x-2.0. Target entity type should be set only in constructor.
   */
  public function setTargetEntityTypeId($entity_type_id);

  /**
   * Gets the bundles of the target entity type.
   *
   * @param bool $applicable
   *   (optional) If TRUE, get all the applicable bundles if none set.
   *
   * @return string[]
   *   An array containing the applicable bundles. Empty array if none set and
   *   $applicable is FALSE (default), or all existent bundles for the target
   *   entity type if none was set and $applicable is TRUE.
   */
  public function getBundles($applicable = FALSE);

  /**
   * Gets the transactor plugin ID for this transaction type.
   *
   * @return string
   *   The transactor plugin ID.
   */
  public function getPluginId();

  /**
   * Set the transactor plugin.
   *
   * Calling this method reset the current plugin and its settings.
   *
   * @param string $plugin_id
   *   A string containing the flag type plugin ID.
   *
   * @return \Drupal\transaction\TransactionTypeInterface
   *   The called transaction type.
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the plugin settings.
   *
   * @return array
   *   A key-value map of settings. Empty array if no settings.
   */
  public function getPluginSettings();

  /**
   * Sets the plugin settings.
   *
   * @param array $settings
   *   A key-value map with the settings.
   *
   * @return \Drupal\transaction\TransactionTypeInterface
   *   The called transaction type.
   */
  public function setPluginSettings(array $settings);

  /**
   * Gets the transactor plugin for this transaction type.
   *
   * @return \Drupal\transaction\TransactorPluginInterface
   *   The transactor plugin.
   */
  public function getPlugin();

  /**
   * Gets a transaction type additional option value.
   *
   * @param string $name
   *   The option to retrieve.
   * @param mixed $default_value
   *   Default value to return if no such option or it is empty.
   *
   * @return mixed
   *   The option current value or the default value if not available.
   */
  public function getOption($name, $default_value = NULL);

  /**
   * Gets a map of all defined options.
   *
   * @return array
   *   Current options keyed by it name. Empty array if no options set.
   */
  public function getOptions();

  /**
   * Sets a transaction type option.
   *
   * @param string $name
   *   The option to set.
   * @param mixed $value
   *   The new value.
   *
   * @return \Drupal\transaction\TransactionTypeInterface
   *   The called transaction type.
   */
  public function setOption($name, $value);

  /**
   * Sets the transaction type options, replacing the currently set.
   *
   * @param array $options
   *   The new set of options.
   *
   * @return \Drupal\transaction\TransactionTypeInterface
   *   The called transaction type.
   */
  public function setOptions(array $options);

  /**
   * Check if the type of transaction is applicable to a particular entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to check.
   *
   * @return bool
   *   TRUE if transaction type is applicable to the given entity.
   */
  public function isApplicable(ContentEntityInterface $entity);

}
