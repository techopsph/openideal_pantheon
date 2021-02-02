<?php

namespace Drupal\transaction\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\transaction\TransactionServiceInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an action to fetch the last executed transaction of a given target.
 *
 * @RulesAction(
 *   id = "transaction_fetch_last_executed",
 *   label = @Translation("Fetch the last executed transaction"),
 *   category = @Translation("Transaction"),
 *   context = {
 *     "target_entity" = @ContextDefinition("entity",
 *       label = @Translation("Target entity"),
 *       description = @Translation("The target entity of the transaction to search for."),
 *       assignment_restriction = "selector"
 *     ),
 *     "transaction_type_id" = @ContextDefinition("string",
 *       label = @Translation("Transaction type"),
 *       description = @Translation("The transaction type to search for."),
 *       assignment_restriction = "input"
 *     )
 *   },
 *   provides = {
 *     "transaction_last_executed" = @ContextDefinition("entity:transaction",
 *       label = @Translation("The fetched last executed transaction")
 *     )
 *   }
 * )
 */
class LastTransactionFetch extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The transaction service.
   *
   * @var \Drupal\transaction\TransactionServiceInterface
   */
  protected $transactionService;

  /**
   * Constructs an LastTransactionFetch object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\transaction\TransactionServiceInterface $transaction_service
   *   The transaction service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransactionServiceInterface $transaction_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transactionService = $transaction_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('transaction')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function refineContextDefinitions(array $selected_data) {
    if ($transaction_type_id = $this->getContextValue('transaction_type_id')) {
      $data_type = 'entity:transaction:' . $transaction_type_id;
      $this->pluginDefinition['provides']['transaction_last_executed']->setDataType($data_type);
    }
  }

  /**
   * Gets the last executed transaction.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity.
   * @param string $transaction_type_id
   *   The transaction type ID.
   */
  public function doExecute(ContentEntityInterface $target_entity, $transaction_type_id) {
    $transaction = $this->transactionService->getLastExecutedTransaction($target_entity, $transaction_type_id);
    $this->setProvidedValue('transaction_last_executed', $transaction);
  }

}
