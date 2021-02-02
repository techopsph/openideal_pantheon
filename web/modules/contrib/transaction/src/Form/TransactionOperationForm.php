<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\transaction\TransactorHandler;

/**
 * Builds the form to add or edit a transaction operation.
 */
class TransactionOperationForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\transaction\TransactionOperationInterface $transaction_operation */
    $transaction_operation = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $transaction_operation->label(),
      '#description' => $this->t('A short, descriptive title for this transaction operation. It will be used in administrative interfaces.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -3,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $transaction_operation->id(),
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      '#weight' => -2,
      '#machine_name' => [
        'exists' => '\Drupal\transaction\Entity\TransactionOperation::load',
      ],
      '#disabled' => !$transaction_operation->isNew(),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description template'),
      '#default_value' => $transaction_operation->getDescription(),
      '#description' => $this->t('The description template for this operation. It admits tokens that will replaced with values from the transaction or the target entity.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => 10,
    ];

    $form['details'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description detail templates'),
      '#default_value' => implode("\n", $transaction_operation->getDetails()),
      '#description' => $this->t('Template for additional details for this operation. Enter one line per detail entry template. It admits tokens that will replaced with values from the transaction or the target entity.'),
      '#rows' => 5,
      '#cols' => 60,
      '#required' => FALSE,
      '#weight' => 10,
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['token_help'] = [
        '#title' => $this->t('Replacement patterns'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#weight' => 20,
      ];

      if ($transaction_type_id = $transaction_operation->getTransactionTypeId()) {
        $transaction_type = $this->entityTypeManager->getStorage('transaction_type')->load($transaction_type_id);
      }
      else {
        $transaction_type = $this->getRequest()->get('transaction_type');
      }

      $form['token_help']['browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [
          'transaction',
          TransactorHandler::getTokenContextFromEntityTypeId($transaction_type->getTargetEntityTypeId()),
        ],
      ];
    }
    else {
      $form['token_help'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Enable the <a href=":url">Token module</a> to view the available token browser.', [':url' => 'https://www.drupal.org/project/token']),
        '#weight' => 20,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save transaction operation');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionOperationInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // On new operations, set the transaction type in form values from request.
    if ($entity->isNew()) {
      $transaction_type = $this->getRequest()->get('transaction_type');
      $form_state->setValue('transaction_type', is_string($transaction_type) ? $transaction_type : $transaction_type->id());
    }

    // Process the details textarea.
    $details = !empty($submitted_details = $form_state->getValue('details'))
      ? explode("\n", $submitted_details)
      : [];
    $entity->setDetails($details);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionOperationInterface $transaction_operation */
    $transaction_operation = $this->entity;

    $status = $transaction_operation->save();

    // Messages.
    $t_args = ['%label' => $transaction_operation->label()];
    $logger_args = $t_args + ['link' => $transaction_operation->toLink($this->t('Edit'), 'edit-form')->toString()];
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Transaction operation %label has been updated.', $t_args));
      $this->logger('transaction')->notice('Transaction operation %label has been updated.', $logger_args);
    }
    else {
      $this->messenger()->addStatus($this->t('Transaction operation %label has been added.', $t_args));
      $this->logger('transaction')->notice('Transaction operation %label has been added.', $logger_args);
    }

    $form_state->setRedirect('entity.transaction_operation.collection', ['transaction_type' => $transaction_operation->getTransactionTypeId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionOperationInterface $transaction_operation */
    $transaction_operation = $this->entity;

    $form_state->setRedirect('entity.transaction_operation.collection', ['transaction_type' => $transaction_operation->getTransactionTypeId()]);
  }

}
