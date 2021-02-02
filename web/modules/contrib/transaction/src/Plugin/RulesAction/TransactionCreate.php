<?php

namespace Drupal\transaction\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides the transaction create action.
 *
 * @RulesAction(
 *   id = "transaction_create",
 *   deriver = "Drupal\transaction\Plugin\RulesAction\TransactionCreateDeriver",
 * )
 */
class TransactionCreate extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an TransactionCreate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('rules')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function refineContextDefinitions(array $selected_data) {
    if ($transaction_type_id = $this->getContextValue('transaction_type_id')) {
      $data_type = 'entity:transaction:' . $transaction_type_id;
      $this->pluginDefinition['provides']['transaction']->setDataType($data_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $values = $this->getContextValues();
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    if (!$transaction_type = $this->entityTypeManager->getStorage('transaction_type')->load($values['transaction_type_id'])) {
      // Transaction type not found.
      $this->logger->error('Transaction type @type not found in action rule %rule', ['@type' => $values['transaction_type_id'], '%rule' => $this->getLabelValue()]);
      return;
    }

    // Ensure that the transaction type has this transactor.
    if ($transaction_type->getPluginId() != $this->getDerivativeId()) {
      $this->logger->error('Mismatch transactor in rule %rule for transaction type %type', ['%rule' => $this->getLabelValue(), '%type' => $transaction_type->label()]);
      return;
    }

    // Check if the transaction type is applicable to the given target entity.
    $target_entity = $values['target_entity'];
    if (!$transaction_type->isApplicable($target_entity)) {
      $this->logger->error('Transaction type %type not applicable to the target entity %target', ['%type' => $transaction_type->label(), '%target' => $target_entity->label()]);
      return;
    }

    // Create the transaction.
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->entityTypeManager->getStorage('transaction')->create(
      [
        'type' => $transaction_type->id(),
        'target_entity' => $target_entity,
      ]
    );

    // Set the operation.
    if (!empty($values['operation_id'])) {
      $transaction->setOperation($values['operation_id']);
    }

    // Set field values.
    $settings = $transaction_type->getPluginSettings();
    foreach (['transaction', 'target'] as $field_group) {
      $entity = $field_group == 'transaction' ? $transaction : $target_entity;
      $field_prefix = $field_group . '_field_';
      foreach ($values as $key => $value) {
        if (strpos($key, $field_prefix) === 0) {
          $transactor_field_name = substr($key, strlen($field_prefix));
          if (!empty($settings[$transactor_field_name])
            && $entity->hasField($settings[$transactor_field_name])) {
            $entity->get($settings[$transactor_field_name])->setValue($value);
          }
        }
      }
    }

    $this->setProvidedValue('transaction', $transaction);
  }

}
