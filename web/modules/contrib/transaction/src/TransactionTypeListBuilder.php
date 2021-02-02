<?php

namespace Drupal\transaction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides a entity list page for transaction types.
 */
class TransactionTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The transactor plugin manager.
   *
   * @var \Drupal\transaction\TransactorPluginManager
   */
  protected $transactorManager;

  /**
   * Constructs a new TransactionTypeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\transaction\TransactorPluginManager $transactor_manager
   *   The transactor plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, TransactorPluginManager $transactor_manager) {
    parent::__construct($entity_type, $storage);
    $this->entityTypeManager = $entity_type_manager;
    $this->transactorManager = $transactor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id()),
      $entity_type_manager,
      $container->get('plugin.manager.transaction.transactor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['transactor'] = $this->t('Transactor');
    $header['target_entity_type'] = $this->t('Target entity type');
    $header['target_entity_bundles'] = $this->t('Applicable bundles');
    return $header + parent::buildHeader();
  }

  /**
   * Gets the transactor plugin label for the given transaction type.
   *
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The transaction type.
   *
   * @return array
   *   A render array of the transactor plugin label.
   */
  protected function getTransactorPlugin(TransactionTypeInterface $transaction_type) {
    $transactor = $this->transactorManager
      ->getDefinition($transaction_type->getPluginId());

    return [
      'data' => [
        '#markup' => $transactor['title'],
      ],
    ];
  }

  /**
   * Gets the target entity type label for the given transaction type.
   *
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The transaction type.
   *
   * @return array
   *   A render array of the target entity type label.
   */
  protected function getTargetType(TransactionTypeInterface $transaction_type) {
    $target_entity_type = $this->entityTypeManager
      ->getDefinition($transaction_type->getTargetEntityTypeId());

    return [
      'data' => [
        '#markup' => $target_entity_type->getLabel(),
      ],
    ];
  }

  /**
   * Generates a render array of the applicable bundles.
   *
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The transaction type.
   *
   * @return array
   *   A render array of the applicable bundle's label.
   */
  protected function getTargetBundles(TransactionTypeInterface $transaction_type) {
    $bundles = $transaction_type->getBundles();

    if (empty($bundles)) {
      return [
        'data' => [
          '#markup' => '<em>' . $this->t('All') . '</em>',
          '#allowed_tags' => ['em'],
        ],
      ];
    }

    // Compose bundle labels.
    if ($target_bundle_id = $this->entityTypeManager->getDefinition($transaction_type->getTargetEntityTypeId())->getBundleEntityType()) {
      $target_bundle_storage = $this->entityTypeManager->getStorage($target_bundle_id);
      foreach ($bundles as $key => $bundle) {
        $bundles[$key] = $target_bundle_storage->load($bundle)->label();
      }
    }

    return [
      'data' => [
        '#markup' => implode(', ', $bundles),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\transaction\TransactionTypeInterface $entity */
    $row = [];

    $row['label'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['transactor'] = $this->getTransactorPlugin($entity);
    $row['target_entity_type'] = $this->getTargetType($entity);
    $row['target_entity_bundles'] = $this->getTargetBundles($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No transaction types available. <a href=":link">Add a transaction type</a>.', [
      ':link' => Url::fromRoute('transaction.transaction_type_creation')->toString(),
    ]);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    return [
      'operations' => [
        'title' => $this->t('Transaction operations'),
        'weight' => 50,
        'url' => Url::fromRoute('entity.transaction_operation.collection', ['transaction_type' => $entity->id()]),
      ],
    ] + parent::getDefaultOperations($entity);
  }

}
