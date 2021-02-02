<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\transaction\TransactorPluginManager;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the transaction type creation form.
 *
 * The target entity type and the transaction plugin cannot be changed once
 * a transaction type is created, so we need a creation page to select this
 * two paramenters when creating a new transaction type.
 *
 * @see \Drupal\transaction\TransactionTypeInterface
 */
class TransactionTypeCreationForm extends FormBase {

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
   * Constructs a new transaction type creation form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\transaction\TransactorPluginManager $transactor_manager
   *   The transactor plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TransactorPluginManager $transactor_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->transactorManager = $transactor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.transaction.transactor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transaction_type_creation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $entity_types[$entity_type_id] = $entity_type->getLabel();
      }
    }
    asort($entity_types);

    $form['target_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Target entity type'),
      '#description' => $this->t('The target entity type. This cannot be changed once the transaction type is created.'),
      '#options' => $entity_types,
      '#default_value' => 'node',
      '#required' => TRUE,
    ];

    $form['transactor'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transactor'),
      '#description' => $this->t('Select the plugin type that operates the transaction. This cannot be changed once the transaction type is created.'),
      '#options' => [],
      '#required' => TRUE,
    ];
    foreach ($this->transactorManager->getTransactors() as $id => $info) {
      $form['transactor']['#options'][$id] = $info['title'];
      $form['transactor'][$id]['#description'] = $info['description'];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.transaction_type.add_form', [
      'target_entity_type' => $form_state->getValue('target_entity_type'),
      'transactor' => $form_state->getValue('transactor'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (($transactor_id = $form_state->getValue('transactor'))
      && ($transactor_info = $this->transactorManager->getTransactor($transactor_id))) {
      // Check for supported entity types.
      if (!empty($transactor_info['supported_entity_types'])
        && !in_array($target_entity_type_id = $form_state->getValue('target_entity_type'), $transactor_info['supported_entity_types'])) {
        $form_state->setErrorByName('target_entity_type', $this->t('The %type entity type is not supported by the @transactor transactor.', [
          '%type' => $target_entity_type_id ? $this->entityTypeManager->getDefinition($target_entity_type_id)->getLabel() : $this->t('Undefined'),
          '@transactor' => $transactor_info['title'],
        ]));
      }
    }
    else {
      // Empty or invalid transactor.
      $form_state->setErrorByName('transactor', $this->t('Invalid transactor.'));
    }

  }

}
