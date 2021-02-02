<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for transaction execution.
 */
class TransactionExecuteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Execute %label transaction?', ['%label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->getEntity();
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $transaction->get('type')->entity;

    return $transaction_type->getPlugin()->getExecutionIndications($transaction) ?: parent::getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\transaction\TransactionInterface $entity */
    $entity = $this->getEntity();
    return $entity->getTargetEntityId()
      ? $entity->toUrl('collection', ['target_entity' => $entity->getTargetEntityId()])
      : $entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $this->getEntity();

    // ContentEntityForm::buildEntity sets the entity validation required, but
    // ContentEntityConfirmFormBase overrides the validateForm method, so the
    // entity finally remains in a state that throws an "Entity validation was
    // skipped" exception when trying to save it. Unsetting the validation
    // required here as not needed.
    $transaction->setValidationRequired(FALSE);

    if ($transaction->execute()) {
      $this->messenger()->addStatus($this->t(
        'Transaction @label executed successfully.',
        ['@label' => $transaction->label()]));
    }
    else {
      $this->messenger()->addError($transaction->getResultMessage()
        ?: $this->t('There was an error executing @label transaction.', ['@label' => $transaction->label()]));
    }

    $form_state->setRedirectUrl($transaction->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Execute transaction');
  }

}
