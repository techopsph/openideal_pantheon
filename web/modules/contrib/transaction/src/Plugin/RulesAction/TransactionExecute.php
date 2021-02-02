<?php

namespace Drupal\transaction\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\transaction\TransactionInterface;

/**
 * Provides the transaction execute action.
 *
 * @RulesAction(
 *   id = "transaction_execute",
 *   label = @Translation("Execute a transaction"),
 *   category = @Translation("Transaction"),
 *   context = {
 *     "transaction" = @ContextDefinition("entity:transaction",
 *       label = @Translation("Transaction"),
 *       description = @Translation("Specifies the transaction to execute."),
 *       assignment_restriction = "selector"
 *     ),
 *     "immediate" = @ContextDefinition("boolean",
 *       label = @Translation("Force saving immediately"),
 *       description = @Translation("Usually saving is postponed till the end of the evaluation, so that multiple saves can be fold into one. If this set, saving is forced to happen immediately."),
 *       default_value = FALSE,
 *       required = FALSE
 *     )
 *   }
 * )
 */
class TransactionExecute extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Flag that indicates if the entity should be auto-saved later.
   *
   * @var bool
   */
  protected $saveLater = TRUE;

  /**
   * Constructs an TransactionExecute object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('rules')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function refineContextDefinitions(array $selected_data) {
    if (isset($selected_data['transaction'])
      && $bundle_constraint = $selected_data['transaction']->getConstraint('Bundle')) {
      $data_type = 'entity:transaction:' . $bundle_constraint[0];
      $this->pluginDefinition['context']['transaction']->setDataType($data_type);
    }
  }

  /**
   * Executes a transaction.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The transaction to execute.
   * @param bool $immediate
   *   (optional) Save the transaction immediately after its execution.
   */
  public function doExecute(TransactionInterface $transaction, $immediate = FALSE) {
    // Transaction cannot be executed.
    if (!$transaction->isPending()) {
      $this->logger->error('Transaction %label with ID @id already executed', ['%label' => $transaction->label(), '@id' => $transaction->id()]);
      return;
    }

    $transaction->execute($immediate);
    $this->saveLater = !$immediate;
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    if ($this->saveLater) {
      return ['transaction'];
    }
    return [];
  }

}
