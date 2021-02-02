<?php

namespace Drupal\Tests\transaction\Kernel;

use Drupal\transaction\Entity\TransactionType;
use Drupal\user\Entity\User;

/**
 * Tests the generic transactor.
 *
 * @group transaction
 */
class GenericTransactionTest extends KernelTransactionTestBase {

  /**
   * Creates a generic transaction type.
   */
  protected function prepareTransactionType() {
    $this->transactionType = TransactionType::create([
      'id' => 'test_generic',
      'label' => 'Test generic',
      'target_entity_type' => 'entity_test',
      'transactor' => [
        'id' => 'transaction_generic',
        'settings' => [
          'last_transaction' => 'field_last_transaction',
          'log_message' => 'field_log_message',
        ],
      ],
    ]);
    $this->transactionType->save();

    // Adds the test log message field.
    $this->addTransactionLogMessageField();
  }

  /**
   * Tests generic transaction creation.
   */
  public function testGenericTransactionCreation() {
    $transaction = $this->transaction;

    // Checks status for new non-executed transaction.
    $this->assertEquals($this->targetEntity, $transaction->getTargetEntity());
    $this->assertEquals('Unsaved transaction (pending)', $transaction->getDescription());
    $this->assertEquals([$this->logMessage], $transaction->getDetails());
    $this->assertNull($transaction->getExecutionTime());
    $this->assertNull($transaction->getExecutor());
    $this->assertFalse($transaction->getResultCode());
    $this->assertFalse($transaction->getResultMessage());

    $transaction->save();
    $this->assertEquals('Transaction 1 (pending)', $transaction->getDescription());
  }

  /**
   * Tests generic transaction execution.
   */
  public function testGenericTransactionExecution() {
    $transaction = $this->transaction;

    $this->assertTrue($transaction->execute());
    // Checks the transaction status after its execution.
    $this->assertEquals('Transaction 1', $transaction->getDescription());
    $this->assertNotNull($transaction->getExecutionTime());
    $this->assertEquals(User::getAnonymousUser()->id(), $transaction->getExecutorId());
    $this->assertGreaterThan(0, $transaction->getResultCode());
    $this->assertEquals('Transaction executed successfully.', $transaction->getResultMessage());
    $this->assertEquals($transaction->id(), $this->targetEntity->get('field_last_transaction')->target_id);
  }

  /**
   * Tests generic transaction execution with operation.
   */
  public function testGenericTransactionOperation() {
    $transaction = $this->transaction;

    // Sets an operation to the transaction and executes it.
    $transaction->setOperation($this->transactionOperation)->execute();
    // Checks if the transaction description and details are composed from their
    // templates in the operation.
    $this->assertEquals('Test generic #1', $transaction->getDescription());
    $expected_details = [
      $this->logMessage,
      'Executed by UID: ' . $transaction->getExecutorId(),
      'Transaction UUID: ' . $transaction->uuid(),
    ];
    $this->assertEquals($expected_details, $transaction->getDetails());
  }

  /**
   * Tests the transaction execution sequence.
   */
  public function testTransactionExecutionSequence() {
    $first_transaction = $this->transaction;
    // Creates a new transaction.
    $this->prepareTransaction();
    $second_transaction = $this->transaction;
    // Executes both transaction within the same request.
    $first_transaction->execute();
    $second_transaction->execute();

    // Checks that both transactions have the same execution time.
    $this->assertEquals($first_transaction->getExecutionTime(), $second_transaction->getExecutionTime());

    // Checks that the second transaction is the last execution transaction.
    /** @var \Drupal\transaction\TransactionInterface $last_transaction */
    $last_transaction = \Drupal::service('transaction')
      ->getLastExecutedTransaction($this->targetEntity, $this->transactionType);
    $this->assertEquals($second_transaction->id(), $last_transaction->id());
  }

}
