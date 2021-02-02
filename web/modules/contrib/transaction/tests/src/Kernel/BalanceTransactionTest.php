<?php

namespace Drupal\Tests\transaction\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\transaction\Entity\Transaction;
use Drupal\transaction\Entity\TransactionType;

/**
 * Tests the balance transactor.
 *
 * @group transaction
 */
class BalanceTransactionTest extends KernelTransactionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareTransactionType() {
    $this->transactionType = TransactionType::create([
      'id' => 'test_balance',
      'label' => 'Test balance',
      'target_entity_type' => 'entity_test',
      'transactor' => [
        'id' => 'transaction_balance',
        'settings' => [
          'last_transaction' => 'field_last_transaction',
          'log_message' => 'field_log_message',
          'amount' => 'field_amount',
          'balance' => 'field_balance',
          'target_balance' => 'field_balance',
        ],
      ],
    ]);
    $this->transactionType->save();

    $this->prepareTransactionAmountField();
    $this->prepareTransactionBalanceField();
    $this->prepareTargetEntityBalanceField();

    // Adds the test log message field.
    $this->addTransactionLogMessageField();
  }

  /**
   * Creates the amount field in the transaction entity type.
   */
  protected function prepareTransactionAmountField() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_amount',
      'type' => 'decimal',
      'entity_type' => 'transaction',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'label' => 'Amount',
      'field_storage' => $field_storage,
      'bundle' => $this->transactionType->id(),
    ])->save();
  }

  /**
   * Creates the balance field in the transaction entity type.
   */
  protected function prepareTransactionBalanceField() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_balance',
      'type' => 'decimal',
      'entity_type' => 'transaction',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'label' => 'Balance',
      'field_storage' => $field_storage,
      'bundle' => $this->transactionType->id(),
    ])->save();
  }

  /**
   * Creates the balance field in the target entity type.
   */
  protected function prepareTargetEntityBalanceField() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_balance',
      'type' => 'decimal',
      'entity_type' => 'entity_test',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'label' => 'Balance',
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests balance transaction creation.
   */
  public function testBalanceTransactionCreation() {
    $transaction = $this->transaction;

    // Checks status for new non-executed transaction.
    $this->assertEquals('Unsaved transaction (pending)', $transaction->getDescription());
    $transaction->save();
    $this->assertEquals('Zero amount transaction (pending)', $transaction->getDescription());
    $this->assertEquals([$this->logMessage], $transaction->getDetails());
    $transaction->set('field_amount', -10);
    $this->assertEquals('Debit transaction (pending)', $transaction->getDescription(TRUE));
    $transaction->set('field_amount', 10);
    $this->assertEquals('Credit transaction (pending)', $transaction->getDescription(TRUE));
  }

  /**
   * Tests balance transaction execution.
   */
  public function testBalanceTransactionExecution() {
    $transaction = $this->transaction;

    // Set an initial balance.
    $transaction->set('field_balance', 10);
    $transaction->set('field_amount', 10);

    $this->assertTrue($transaction->execute());
    // Checks the transaction status after its execution.
    $this->assertEquals($transaction->id(), $this->targetEntity->get('field_last_transaction')->target_id);
    $this->assertGreaterThan(0, $transaction->getResultCode());
    $this->assertEquals('Transaction executed successfully.', $transaction->getResultMessage());
    $this->assertEquals('Credit transaction', trim($transaction->getDescription()));
    // Checks the result balance.
    $this->assertEquals(20, $transaction->get('field_balance')->value);
    $this->assertEquals(20, $this->targetEntity->get('field_balance')->value);

    $second_transaction = Transaction::create([
      'type' => $this->transactionType->id(),
      'target_entity' => $this->targetEntity,
      'field_log_message' => $this->logMessage,
      // Initial balance must be ignored when at least one transactions was
      // previously executed.
      'field_balance' => 50,
      'field_amount' => -10,
    ]);
    $this->targetEntity->set('field_balance', 50);

    $this->assertTrue($second_transaction->execute());
    // Checks the new transaction status.
    $this->assertEquals('Debit transaction', trim($second_transaction->getDescription()));
    // Checks the result balance.
    $this->assertEquals(10, $second_transaction->get('field_balance')->value);
    $this->assertEquals(10, $this->targetEntity->get('field_balance')->value);
    $this->assertEquals($second_transaction->id(), $this->targetEntity->get('field_last_transaction')->target_id);
  }

}
