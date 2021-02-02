<?php

namespace Drupal\transaction\Plugin\RulesAction;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rules\Context\ContextDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\transaction\TransactorPluginManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\rules\Context\ContextDefinitionInterface;

/**
 * Derives transaction create plugin definitions based on transaction types.
 *
 * @see \Drupal\transaction\Plugin\RulesAction\TransactionCreate
 */
class TransactionCreateDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The transactor plugin manager.
   *
   * @var \Drupal\transaction\TransactorPluginManager
   */
  protected $transactorManager;

  /**
   * Creates a new TransactionCreateDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\transaction\TransactorPluginManagerInterface $transactor_manager
   *   The transactor plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TransactorPluginManagerInterface $transactor_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->transactorManager = $transactor_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.transaction.transactor'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->transactorManager->getTransactors() as $transactor_id => $transactor_info) {
      $contexts = [];

      // Transaction type context.
      $contexts['transaction_type_id'] = ContextDefinition::create('string')
        ->setLabel($this->t('Transaction type'))
        ->setRequired(TRUE)
        ->setAssignmentRestriction(ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT)
        ->setDescription($this->t('The transaction type ID for the new transaction.'));

      // Target entity context.
      $contexts['target_entity'] = ContextDefinition::create('entity')
        ->setLabel($this->t('Target entity'))
        ->setRequired(TRUE)
        ->setAssignmentRestriction(ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR)
        ->setDescription($this->t('The target entity for the new transaction.'));

      // Transaction type context.
      $contexts['operation_id'] = ContextDefinition::create('string')
        ->setLabel($this->t('Operation'))
        ->setRequired(FALSE)
        ->setAssignmentRestriction(ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT)
        ->setDescription($this->t('An optional transaction operation.'));

      // Add transactor fields.
      foreach (['transaction', 'target'] as $field_group) {
        foreach ($transactor_info[$field_group . '_fields'] as $field_info) {
          $field_definition = BaseFieldDefinition::create($field_info['type']);
          $field_data_type = $field_definition->getPropertyDefinition($field_definition->getMainPropertyName())->getDataType();
          $contexts[$field_group . '_field_' . $field_info['name']] = ContextDefinition::create($field_data_type)
            ->setLabel($field_info['title'])
            ->setRequired($field_info['required'])
            ->setDescription($field_info['description']);
        }
      }

      // Add the derivative.
      $this->derivatives[$transactor_id] = [
        'label' => $this->t('Create a new @transactor_type transaction', ['@transactor_type' => $transactor_info['title']]),
        'category' => $this->t('Transaction'),
        'context' => $contexts,
        'provides' => [
          'transaction' => ContextDefinition::create('entity:transaction')
            ->setLabel($this->t('Transaction'))
            ->setRequired(TRUE),
        ],
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
