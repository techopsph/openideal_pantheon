<?php

namespace Drupal\Tests\userpoints\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\transaction\Kernel\KernelTransactionTestBase;
use Drupal\transaction\Entity\Transaction;
use Drupal\transaction\Entity\TransactionType;
use Drupal\user\Entity\User;

/**
 * Tests the user points transactor.
 *
 * @group userpoints
 */
class UserPointsTransactionTest extends KernelTransactionTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'userpoints',
  ];

  /**
   * {@inheritdoc}
   */
  protected function prepareTargetEntity() {
    $this->installSchema('system', 'sequences');
    $this->targetEntity = User::create([
      'name' => 'admin',
    ]);
    $this->targetEntity->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareTransactionType() {
    $this->transactionType = TransactionType::create([
      'id' => 'test_userpoints',
      'label' => 'Test user points',
      'target_entity_type' => 'user',
      'transactor' => [
        'id' => 'userpoints',
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
      'label' => 'Points amount',
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
      'label' => 'Points balance',
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
      'entity_type' => 'user',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'label' => 'Points',
      'field_storage' => $field_storage,
      'bundle' => 'user',
    ])->save();
  }

  /**
   * Creates an entity reference field to the latest executed transaction.
   */
  protected function prepareTargetEntityLastTransactionField() {
    // Entity reference field to the last executed transaction.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_last_transaction',
      'type' => 'entity_reference',
      'entity_type' => 'user',
      'settings' => [
        'target_type' => 'transaction',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'label' => 'Last transaction',
      'field_storage' => $field_storage,
      'bundle' => 'user',
    ])->save();
  }

  /**
   * Tests user points transaction creation.
   *
   * @covers \Drupal\userpoints\Plugin\Transaction\UserPointsTransactor
   */
  public function testUserPointsTransactionCreation() {
    $transaction = $this->transaction;
    $transactor = $transaction->getType()->getPlugin();

    // Checks status for new non-executed transaction.
    $this->assertEquals('Zero points transaction (pending)', $transaction->getDescription());
    $this->assertEquals('The current user points balance will not be altered.', $transactor->getExecutionIndications($transaction));
    $this->assertEquals([$this->logMessage], $transaction->getDetails());
    $transaction->set('field_amount', -10);
    $this->assertEquals('Points debit (pending)', $transaction->getDescription(TRUE));
    $this->assertEquals('The user will loss 10 points.', $transactor->getExecutionIndications($transaction));
    $transaction->set('field_amount', 10);
    $this->assertEquals('Points credit (pending)', $transaction->getDescription(TRUE));
    $this->assertEquals('The user will gain 10 points.', $transactor->getExecutionIndications($transaction));
  }

  /**
   * Tests user points transaction execution.
   *
   * @covers \Drupal\userpoints\Plugin\Transaction\UserPointsTransactor
   */
  public function testUserPointsTransactionExecution() {
    $transaction = $this->transaction;

    // Set an initial balance.
    $transaction->set('field_balance', 10);
    $transaction->set('field_amount', 10);

    $this->assertTrue($transaction->execute());
    // Checks the transaction status after its execution.
    $this->assertEquals($transaction->id(), $this->targetEntity->get('field_last_transaction')->target_id);
    $this->assertGreaterThan(0, $transaction->getResultCode());
    $this->assertEquals('Transaction executed successfully.', $transaction->getResultMessage());
    $this->assertEquals('Points credit', trim($transaction->getDescription()));
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
    $this->assertEquals('Points debit', trim($second_transaction->getDescription()));
    // Checks the result balance.
    $this->assertEquals(10, $second_transaction->get('field_balance')->value);
    $this->assertEquals(10, $this->targetEntity->get('field_balance')->value);
    $this->assertEquals($second_transaction->id(), $this->targetEntity->get('field_last_transaction')->target_id);
  }

}
