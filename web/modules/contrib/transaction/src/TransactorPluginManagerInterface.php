<?php

namespace Drupal\transaction;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Plugin manager interface.
 */
interface TransactorPluginManagerInterface extends PluginManagerInterface {

  /**
   * Gets the plugin information for all available transactors.
   *
   * @return array
   *   Array of all transactors titles keyed by plugin ID.
   */
  public function getTransactors();

  /**
   * Gets the plugin information for a transactor.
   *
   * @param string $transactor_id
   *   The ID of the requested transactor information.
   *
   * @return array
   *   The transactor plugin information. NULL if no such transactor.
   */
  public function getTransactor($transactor_id);

}
