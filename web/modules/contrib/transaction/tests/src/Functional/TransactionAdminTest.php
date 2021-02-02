<?php

namespace Drupal\Tests\transaction\Functional;

use Drupal\transaction\Entity\TransactionOperation;
use Drupal\transaction\Entity\TransactionType;

/**
 * Tests the transaction administration.
 *
 * @group transaction
 */
class TransactionAdminTest extends FunctionalTransactionTestBase {

  /**
   * A test user with permission to administer transactions types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer transaction types',
      'administer entity_test fields',
      'administer transaction fields',
      'access administration pages',
      'view the administration theme',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add some basic blocks.
    $block_options = ['region' => 'content'];
    // Page title block.
    $this->drupalPlaceBlock('page_title_block', $block_options);
    // Local task links.
    $this->drupalPlaceBlock('local_tasks_block', $block_options);
    // Primary admin actions.
    $this->drupalPlaceBlock('local_actions_block', $block_options);
  }

  /**
   * Full admin test sequence.
   */
  public function testAdmin() {
    $this->doTestAddBasicTransactionType();
    $this->doTestSetTransactionTypeFieldCreation();
    $this->doTestTransactorConfiguration();
    $this->doTestAddTargetEntityTypeLocalTask();
    $this->doTestRemoveTransactionType();
  }

  /**
   * Tests the transaction type add form.
   *
   * @see \Drupal\transaction\Form\TransactionTypeAddForm
   * @see \Drupal\transaction\Form\TransactionTypeEditForm
   */
  public function doTestAddBasicTransactionType() {
    // Go to export (restore) block with the default content page.
    $this->drupalGet('admin/config/workflow/transaction');
    $this->clickLink('Add transaction type');
    // Confirm the form.
    $transactor = 'transaction_generic';
    $target_entity_type = 'entity_test';
    $post = ['target_entity_type' => $target_entity_type, 'transactor' => $transactor];
    $this->drupalPostForm(NULL, $post, 'Continue');

    $label = 'Generic workflow';
    $id = 'generic_workflow';
    $post = [
      'label' => $label,
      'id' => $id,
    ];
    $this->drupalPostForm(NULL, $post, 'Create transaction type');

    // Check the created transaction type values.
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = TransactionType::load($id);
    $this->assertNotNull($transaction_type);
    $this->assertEqual($transaction_type->label(), $label);
    $this->assertEqual($transaction_type->getTargetEntityTypeId(), $target_entity_type);
    $this->assertEqual($transaction_type->getPluginId(), $transactor);

    /** @var \Drupal\transaction\TransactorPluginInterface $transactor_plugin */
    $transactor_plugin = $transaction_type->getPlugin();
    // Check that the transactor has no configuration (uses default options).
    $this->assertEmpty($transactor_plugin->getConfiguration());
  }

  /**
   * Tests field creation from the transaction type edit.
   *
   * @see \Drupal\transaction\Form\TransactionTypeEditForm
   */
  public function doTestSetTransactionTypeFieldCreation() {
    $post = [
      'log_message' => '_create',
      'log_message_field_name' => 'log_message',
      'last_transaction' => '_create',
      'last_transaction_label' => 'Last transaction',
      'last_transaction_field_name' => 'last_transaction',
    ];
    $this->drupalPostForm('admin/config/workflow/transaction/edit/generic_workflow', $post, 'Save transaction type');

    // Check the log message field was created on the transaction type.
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $fields = $entity_field_manager->getFieldDefinitions('transaction', 'generic_workflow');
    $this->assertTrue(isset($fields['field_log_message']));
    $fields = $entity_field_manager->getFieldDefinitions('entity_test', 'basic');
    $this->assertTrue(isset($fields['field_last_transaction']));
  }

  /**
   * Tests transactor configuration from the transaction type edit.
   *
   * @see \Drupal\transaction\Form\TransactionTypeEditForm
   */
  public function doTestTransactorConfiguration() {
    $post = [
      // Execution control: ask user.
      'execution' => '3',
    ];
    $this->drupalPostForm('admin/config/workflow/transaction/edit/generic_workflow', $post, 'Save transaction type');

    // Checks the transactor options.
    $expected_plugin_configuration = [
      'log_message' => 'field_log_message',
      'last_transaction' => 'field_last_transaction',
      'execution' => '3',
    ];
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = TransactionType::load('generic_workflow');
    /** @var \Drupal\transaction\TransactorPluginInterface $transactor_plugin */
    $transactor_plugin = $transaction_type->getPlugin();
    // Check that the transactor has the expected configuration.
    $this->assertEquals($transactor_plugin->getConfiguration(), $expected_plugin_configuration);
  }

  /**
   * Tests add local task on target entity type from the transaction type edit.
   *
   * @see \Drupal\transaction\Form\TransactionTypeEditForm
   */
  public function doTestAddTargetEntityTypeLocalTask() {
    $post = [
      // Execution control: ask user.
      'local_task' => TRUE,
    ];
    $this->drupalPostForm('admin/config/workflow/transaction/edit/generic_workflow', $post, 'Save transaction type');

    // Check that the option were saved.
    /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
    $transaction_type = TransactionType::load('generic_workflow');
    $transaction_type_options = $transaction_type->getOptions();
    $this->assertNotEmpty($transaction_type_options['local_task']);

    // Check that the target entity type transaction list route was added.
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');
    $route = $route_provider->getRouteByName('entity.entity_test.generic_workflow-transaction');
    $this->assertNotNull($route);
  }

  /**
   * Tests the transaction operation creation from the admin UI.
   *
   * @see \Drupal\transaction\Form\TransactionOperationForm
   */
  public function doTestAddTransactionOperation() {
    // Go to export (restore) block with the default content page.
    $this->drupalGet('admin/config/workflow/transaction/edit/generic_workflow/operation');
    // It must be the first transaction operation to be added.
    $this->assertSession()->pageTextContains('No transaction operations available.');
    $this->clickLink('Add transaction operation');
    $post = [
      'label' => 'Test operation',
      'id' => 'test_operation',
      'description' => 'Transaction operation description',
      'details' => 'Details line line 1' . PHP_EOL . 'Details line line 2',
    ];
    $this->drupalPostForm(NULL, $post, 'Save transaction operation');

    // Check the creation message.
    $this->assertSession()->pageTextContains('Transaction operation Test operation has been added.');

    // Check the created transaction type values.
    /** @var \Drupal\transaction\TransactionOperationInterface $transaction_operation */
    $transaction_operation = TransactionOperation::load('test_operation');
    $this->assertEquals($transaction_operation->getTransactionTypeId(), 'generic_workflow');
    $this->assertEquals($transaction_operation->label(), $post['label']);
    $this->assertEquals($transaction_operation->getDescription(), $post['description']);
    $details = explode(PHP_EOL, $post['details']);
    $this->assertEquals($transaction_operation->getDetails(), $details);
  }

  /**
   * Tests transaction type deletion from the admin UI.
   *
   * @see \Drupal\transaction\Form\TransactionTypeDeleteForm
   */
  public function doTestRemoveTransactionType() {
    // Go to the deletion.
    $this->drupalGet('admin/config/workflow/transaction/delete/generic_workflow');
    $this->assertSession()->pageTextContains('Are you sure you want to delete Generic workflow?');
    $this->drupalPostForm(NULL, [], 'Delete');

    $this->assertSession()->pageTextContains('Transaction type Generic workflow deleted.');
    // Check there as no transaction type.
    $this->assertEmpty(TransactionType::loadMultiple());
    // Transaction operations must be gone away as well.
    $this->assertEmpty(TransactionOperation::loadMultiple());
  }

}
