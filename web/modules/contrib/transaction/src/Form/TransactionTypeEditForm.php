<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to edit a transaction type.
 *
 * @todo Is it really necessary?
 */
class TransactionTypeEditForm extends TransactionTypeFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = $this->entity;

    return $form;
  }

}
