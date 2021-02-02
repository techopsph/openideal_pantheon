<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting a transaction.
 */
class TransactionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $msg_args = [
      '@type' => $this->getEntity()->get('type')->entity->label(),
      '%description' => $this->getEntity()->label(),
    ];

    return $this->t('The transaction %description of type @type has been deleted.', $msg_args);
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

}
