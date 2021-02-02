<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\transaction\TransactionTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\transaction\Entity\TransactionType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Base form for transaction type forms.
 */
abstract class TransactionTypeFormBase extends BundleEntityFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs the TransactionTypeFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $this->entity;

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $transaction_type->label(),
      '#description' => $this->t('The human-readable name of this transaction type.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $transaction_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [TransactionType::class, 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this transaction type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    // Set the target entity type from request arguments on creation.
    if ($transaction_type->isNew()) {
      $transaction_type->setTargetEntityTypeId($this->getRequest()->get('target_entity_type'));
    }

    // Applicable bundles.
    $target_entity_type = $this->entityTypeManager->getDefinition($transaction_type->getTargetEntityTypeId());
    if ($target_entity_type->getBundleEntityType()) {
      $bundles = [];
      $definitions = $this->bundleInfo->getBundleInfo($target_entity_type->id());
      foreach ($definitions as $bundle_id => $bundle_metadata) {
        $bundles[$bundle_id] = $bundle_metadata['label'];
      }

      if (count($bundles)) {
        asort($bundles);
        $form['bundles'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Bundles'),
          '#description' => $this->t('Bundles of the target entity type where this transaction type is applicable. Leave empty to apply to all bundles.'),
          '#options' => $bundles,
          '#default_value' => $transaction_type->getBundles(),
        ];
      }
    }

    // Set the transactor plugin id from request arguments on creation.
    if ($transaction_type->isNew()) {
      $transaction_type->setPluginId($this->getRequest()->get('transactor'));
    }

    // General options.
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => TRUE,
      '#tree' => FALSE,
      '#weight' => 50,
    ];

    // Add transaction local task (tab) to target entity.
    $form['options']['execution'] = [
      '#type' => 'radios',
      '#title' => $this->t('Execution control'),
      '#default_value' => $transaction_type->getOption('execution', TransactionTypeInterface::EXECUTION_STANDARD),
      '#options' => [
        TransactionTypeInterface::EXECUTION_STANDARD => $this->t('Leave as pending'),
        TransactionTypeInterface::EXECUTION_IMMEDIATE => $this->t('Immediate execution'),
        TransactionTypeInterface::EXECUTION_ASK => $this->t('Ask user'),
      ],
    ];
    $form['options']['execution'][TransactionTypeInterface::EXECUTION_STANDARD]['#description'] = $this->t('The new transaction can be executed only after its creation.');
    $form['options']['execution'][TransactionTypeInterface::EXECUTION_IMMEDIATE]['#description'] = $this->t('The transaction will be executed automatically right after its creation.');
    $form['options']['execution'][TransactionTypeInterface::EXECUTION_ASK]['#description'] = $this->t('Let the user choose how the new transaction will be executed in the transaction form.');

    // Transaction list local task in the target entity.
    $form['options']['local_task'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add a local task (tab) to access the transaction list in the target entity'),
      '#description' => $this->t('The tab will be labeled with the transaction type name. Disable if you have your own views based transaction list.'),
      '#default_value' => !empty($transaction_type->getOption('local_task')),
    ];

    // Add transactor settings.
    $form = $transaction_type->getPlugin()->buildConfigurationForm($form, $form_state);

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save transaction type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $this->entity;

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, to safe empty check.
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }

    $transaction_type->getPlugin()->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $this->entity;
    // Process options.
    $this->saveOptions($form, $form_state);
    // Plugin needs that the transaction type is saved to create new fields.
    $status = $transaction_type->isNew() ? $transaction_type->save() : SAVED_UPDATED;

    // Set the transactor's config.
    $transaction_type->getPlugin()->submitConfigurationForm($form, $form_state);
    // Update the transaction type.
    $transaction_type->save();

    // Messages.
    $t_args = [
      '%label' => $transaction_type->label(),
      '@transactor' => $transaction_type->getPluginId(),
      '@target' => $transaction_type->getTargetEntityTypeId(),
    ];
    $logger_args = $t_args + ['link' => $transaction_type->toLink($this->t('Edit'), 'edit-form')->toString()];
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Transaction type %label has been updated.', $t_args));
      $this->logger('transaction')->notice('Transaction type %label has been updated.', $logger_args);
    }
    else {
      $this->messenger()->addStatus($this->t('Transaction type %label has been added.', $t_args));
      $this->logger('transaction')->notice('New transaction type %label with transactor @transactor and target entity type @target has been added.', $logger_args);
    }

    $form_state->setRedirectUrl($transaction_type->toUrl('collection'));
  }

  /**
   * Process submitted options.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function saveOptions(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $this->entity;
    $new_options = [];
    foreach (isset($form['options']) ? array_keys($form['options']) : [] as $option_key) {
      if ($value = $form_state->getValue($option_key)) {
        $new_options[$option_key] = $value;
      }
    }
    $transaction_type->setOptions($new_options);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
