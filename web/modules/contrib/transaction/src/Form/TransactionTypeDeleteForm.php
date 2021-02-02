<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete transaction type entities.
 */
class TransactionTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $transaction_count = $this->entityTypeManager->getStorage('transaction')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if ($transaction_count) {
      $form['#title'] = $this->getQuestion();
      $caption = '<p>' . $this->formatPlural($transaction_count,
        'There is 1 transaction of type %type that prevents it from being deleted.',
        'There are @count transactions of type %type that prevents it from being deleted.',
        ['%type' => $this->entity->label()]
      ) . '</p>';
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.transaction_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addStatus(
      $this->t('Transaction type @label deleted.',
        [
          '@label' => $this->entity->label(),
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
