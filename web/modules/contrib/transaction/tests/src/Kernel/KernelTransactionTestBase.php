<?php

namespace Drupal\Tests\transaction\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\transaction\Entity\Transaction;
use Drupal\transaction\Entity\TransactionOperation;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Base class for kernel tests of the Transaction module.
 */
abstract class KernelTransactionTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'filter',
    'text',
    'field',
    'dynamic_entity_reference',
    'token',
    'transaction',
    'entity_test',
  ];

  /**
   * The tested transaction type.
   *
   * @var \Drupal\transaction\TransactionTypeInterface
   *
   * @see \Drupal\Tests\transaction\Kernel\KernelTransactionTestBase::prepareTransactionType()
   */
  protected $transactionType;

  /**
   * A transaction operation to be used in tests.
   *
   * @var \Drupal\transaction\TransactionOperationInterface
   *
   * @see \Drupal\Tests\transaction\Kernel\KernelTransactionTestBase::prepareTransactionOperation()
   */
  protected $transactionOperation;

  /**
   * A transaction to work with in tests.
   *
   * @var \Drupal\transaction\TransactionInterface
   *
   * @see \Drupal\Tests\transaction\Kernel\KernelTransactionTestBase::prepareTransaction()
   */
  protected $transaction;

  /**
   * The target entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $targetEntity;

  /**
   * An arbitrary log message.
   *
   * @var string
   */
  protected $logMessage = 'Log message';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('transaction');
    $this->installEntitySchema('user');

    $this->installConfig(['system', 'user', 'transaction']);

    // Grant the administrative transaction permissions.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('administer transaction types')
      ->grantPermission('administer transactions')
      ->save();

    $this->prepareTransactionLogMessageField();
    $this->prepareTransactionType();
    $this->prepareTransactionOperation();

    $this->prepareTargetEntityLastTransactionField();
    $this->prepareTargetEntity();

    $this->prepareTransaction();
  }

  /**
   * Creates and initializes the transaction type to be tested.
   */
  abstract protected function prepareTransactionType();

  /**
   * Creates and initializes a transaction operation to be tested.
   */
  protected function prepareTransactionOperation() {
    $this->transactionOperation = TransactionOperation::create([
      'id' => 'test_operation',
      'transaction_type' => $this->transactionType->id(),
      'description' => '[transaction:type] #[transaction:id]',
      'details' => [
        'Executed by UID: [transaction:executor:target_id]',
        'Transaction UUID: [transaction:uuid]',
      ],
    ]);
    $this->transactionOperation->save();
  }

  /**
   * Creates and initializes a transaction to be tested.
   */
  protected function prepareTransaction() {
    $this->transaction = Transaction::create([
      'type' => $this->transactionType->id(),
      'target_entity' => $this->targetEntity,
      'field_log_message' => $this->logMessage,
    ]);
  }

  /**
   * Creates an entity reference field to the latest executed transaction.
   */
  protected function prepareTargetEntityLastTransactionField() {
    // Entity reference field to the last executed transaction.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_last_transaction',
      'type' => 'entity_reference',
      'entity_type' => 'entity_test',
      'settings' => [
        'target_type' => 'transaction',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'label' => 'Last transaction',
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Creates the target entity and saves it to be able to be referenced.
   */
  protected function prepareTargetEntity() {
    $this->targetEntity = EntityTest::create(['name' => 'Target entity test']);
    $this->targetEntity->save();
  }

  /**
   * Adds a log message text field to the transaction entity type.
   */
  protected function prepareTransactionLogMessageField() {
    // Log message field in the transaction entity.
    FieldStorageConfig::create([
      'field_name' => 'field_log_message',
      'type' => 'string',
      'entity_type' => 'transaction',
    ])->save();
  }

  /**
   * Adds a log message field to the initialized transaction type to be tested.
   */
  protected function addTransactionLogMessageField() {
    // Adds the test log message field.
    FieldConfig::create([
      'field_name' => 'field_log_message',
      'entity_type' => 'transaction',
      'bundle' => $this->transactionType->id(),
    ])->save();
  }

}
