<?php

namespace Drupal\transaction;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides a entity list page for transaction operations.
 */
class TransactionOperationListBuilder extends ConfigEntityListBuilder {

  /**
   * The ID of the transaction type to which the listed operations belongs.
   *
   * @var string
   */
  protected $transactionTypeId;

  /**
   * Constructs a new TransactionTypeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param string $transaction_type_id
   *   The transaction type ID that operations belongs to.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, $transaction_type_id) {
    parent::__construct($entity_type, $storage);
    $this->transactionTypeId = $transaction_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    // Get the transaction type id from request or route options.
    if ($transaction_type = $container->get('request_stack')->getCurrentRequest()->get('transaction_type')) {
      $transaction_type_id = is_string($transaction_type) ? $transaction_type : $transaction_type->id();
    }
    else {
      $route_options = $container->get('current_route_match')->getRouteObject()->getOptions();
      $transaction_type_id = isset($route_options['_transaction_transaction_type_id']) ? $route_options['_transaction_transaction_type_id'] : '';
    }

    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $transaction_type_id
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->condition('transaction_type', $this->transactionTypeId)
      ->sort('id');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Name');
    $header['description'] = [
      'data' => $this->t('Description template'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['details'] = [
      'data' => $this->t('Detail templates'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\transaction\TransactionOperationInterface $entity */
    $row = [];

    $row['id'] = [
      'data' => $entity->id(),
    ];
    $row['label'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['description'] = [
      'data' => !empty($description = $entity->getDescription())
      ? Unicode::truncate($description, 80, TRUE, TRUE)
      : $this->notAvailableValue(),
    ];
    $row['details'] = [
      'data' => !empty($details = $entity->getDetails())
      ? $this->formatPlural(count($details), '@count line', '@count lines')
      : $this->notAvailableValue(),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * Builds a non-available column value.
   *
   * @return array
   *   Markup array with a 'n/a' value.
   */
  protected function notAvailableValue() {
    return [
      '#markup' => '<em>' . $this->t('n/a') . '</em>',
      '#allowed_tags' => ['em'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No transaction operations available. <a href=":link">Add a transaction operation</a>.', [
      ':link' => Url::fromRoute('entity.transaction_operation.add_form', ['transaction_type' => $this->transactionTypeId])->toString(),
    ]);

    return $build;
  }

}
