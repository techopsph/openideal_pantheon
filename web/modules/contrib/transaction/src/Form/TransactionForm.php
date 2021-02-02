<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\transaction\TransactionTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Form controller for the transaction entity.
 */
class TransactionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $target_entity = NULL) {
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->entity;

    // Set the target entity in the transaction.
    if ($target_entity) {
      $transaction->setTargetEntity($target_entity);
    }

    $form = parent::buildForm($form, $form_state);

    // Grouping status & authoring in tabs.
    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];

    $form['transaction_authoring'] = [
      '#type' => 'details',
      '#title' => $this->t('Transaction authoring'),
      '#open' => TRUE,
      '#group' => 'advanced',
    ];

    $form['uid']['#group'] = 'transaction_authoring';
    $form['created']['#group'] = 'transaction_authoring';

    // Ask for execution.
    if ($transaction->getType()->getOption('execution') == TransactionTypeInterface::EXECUTION_ASK
      && $transaction->isPending()) {
      $form['execute'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Execute'),
        '#weight' => 99,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->entity;

    // This entity form serves specific target entity routes as well, where the
    // target entity argument has the same name that the target entity type.
    if (!$transaction->getTargetEntityId()) {
      $route_options = $this->getRouteMatch()->getRouteObject()->getOptions();
      $target_entity_type_id = isset($route_options['_transaction_target_entity_type_id'])
        ? $route_options['_transaction_target_entity_type_id']
        : $transaction->getType()->getTargetEntityTypeId();
      if ($target_entity = $this->getRequest()->get($target_entity_type_id)) {
        $transaction->setTargetEntity($target_entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->entity;

    // Execute if the user indicated to do so.
    if ($transaction->getType()->getOption('execution') == TransactionTypeInterface::EXECUTION_ASK
      && $form_state->getValue('execute', FALSE)
      && $transaction->isPending()) {
      $executed = $this->entity->execute(FALSE);
    }

    // Save the transaction.
    $saved = parent::save($form, $form_state);
    $msg_args = [
      '@type' => $transaction->getType()->label(),
      '%description' => $transaction->label(),
    ];
    $this->messenger()->addStatus($saved == SAVED_NEW
      ? $this->t('New transaction of type @type has been created.', $msg_args)
      : $this->t('Transaction %description updated.', $msg_args));

    // Executed transaction post save actions.
    if (isset($executed)) {
      // Execution result message.
      if ($result_code = $transaction->getResultCode()) {
        $this->messenger()->addMessage(
          $transaction->getResultMessage(),
          $result_code > 0 ? MessengerInterface::TYPE_STATUS : MessengerInterface::TYPE_ERROR);
      }
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $saved;
  }

}
