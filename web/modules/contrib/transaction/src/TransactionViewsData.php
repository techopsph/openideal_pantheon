<?php

namespace Drupal\transaction;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the transaction entity type.
 */
class TransactionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Description computed field.
    $data['transaction']['description'] = [
      'title' => $this->t('Description'),
      'help' => $this->t('The transaction description, according to the transaction operation or composed by the transactor when there is no operation.'),
      'field' => [
        'id' => 'field',
        'default_formatter' => 'string',
        'field_name' => 'description',
      ],
    ];

    // Details computed field.
    $data['transaction']['details'] = [
      'title' => $this->t('Details'),
      'help' => $this->t('Additional details of a transaction, according to the transaction operation or composed by the transactor when there is no operation.'),
      'field' => [
        'id' => 'field',
        'default_formatter' => 'string',
        'field_name' => 'details',
      ],
    ];

    // Result message computed field.
    $data['transaction']['result_message'] = [
      'title' => $this->t('Result message'),
      'help' => $this->t('A message describing the execution result of the transaction.'),
      'field' => [
        'id' => 'field',
        'default_formatter' => 'string',
        'field_name' => 'result_message',
      ],
    ];

    // Allow the NULL value in executor, execution timestamp and result code.
    $data['transaction']['executed']['filter']['allow empty'] = TRUE;
    $data['transaction']['result_code']['filter']['allow empty'] = TRUE;
    $data['transaction']['executor']['filter']['allow empty'] = TRUE;

    return $data;
  }

}
