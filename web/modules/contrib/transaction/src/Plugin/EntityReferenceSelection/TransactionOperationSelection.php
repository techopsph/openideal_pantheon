<?php

namespace Drupal\transaction\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for transaction operation.
 *
 * @EntityReferenceSelection(
 *   id = "default:transaction_operation",
 *   label = @Translation("Transaction operation selection"),
 *   entity_types = {"transaction_operation"},
 *   group = "default",
 *   weight = 1
 * )
 */
class TransactionOperationSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $configuration = $this->getConfiguration();
    if (isset($configuration['entity'])) {
      $query->condition('transaction_type', $configuration['entity']->getTypeId());
    }

    return $query;
  }

}
