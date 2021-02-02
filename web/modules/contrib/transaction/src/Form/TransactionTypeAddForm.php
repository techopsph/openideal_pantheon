<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to create a transaction type.
 */
class TransactionTypeAddForm extends TransactionTypeFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create transaction type');
    return $actions;
  }

}
