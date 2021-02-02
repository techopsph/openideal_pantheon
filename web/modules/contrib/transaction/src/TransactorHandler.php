<?php

namespace Drupal\transaction;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\transaction\Event\TransactionExecutionEvent;
use Drupal\transaction\Exception\ExecutionTimeoutException;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Datetime\Time;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Transactor entity handler.
 */
class TransactorHandler implements TransactorHandlerInterface {

  /**
   * The transaction service.
   *
   * @var \Drupal\transaction\TransactionServiceInterface
   */
  protected $transactionService;

  /**
   * The transaction entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $transactionStorage;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $timeService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Creates a new TransactorHandler object.
   *
   * @param \Drupal\transaction\TransactionServiceInterface $transaction_service
   *   The transaction service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $transaction_storage
   *   The transaction entity type storage.
   * @param \Drupal\Component\Datetime\Time $time_service
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   */
  public function __construct(TransactionServiceInterface $transaction_service, EntityStorageInterface $transaction_storage, Time $time_service, AccountInterface $current_user, Token $token, EventDispatcherInterface $event_dispatcher, LockBackendInterface $lock) {
    $this->transactionService = $transaction_service;
    $this->transactionStorage = $transaction_storage;
    $this->timeService = $time_service;
    $this->currentUser = $current_user;
    $this->token = $token;
    $this->eventDispatcher = $event_dispatcher;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('transaction'),
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('token'),
      $container->get('event_dispatcher'),
      $container->get('lock')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function doExecute(TransactionInterface $transaction, $save = TRUE, UserInterface $executor = NULL) {
    // Locks the transactional flow for execution, preventing other transactions
    // of the same flow to be executed simultaneously.
    if (!$execution_lock_name = $this->executionLockAcquire($transaction)) {
      throw new ExecutionTimeoutException('Unable to lock the transactional flow for transaction execution.');
    }

    if (!$transaction->isPending()) {
      // Releases the execution lock.
      $this->lock->release($execution_lock_name);
      throw new InvalidTransactionStateException('Cannot execute an already executed transaction.');
    }

    $transaction_type = $transaction->getType();
    $last_executed = $this->transactionService->getLastExecutedTransaction($transaction->getTargetEntityId(), $transaction_type);
    $transactor = $transaction_type->getPlugin();

    if ($transactor->executeTransaction($transaction, $last_executed)) {
      // If no result code set by the transactor, set the generic for
      // successful execution.
      if (!$transaction->getResultCode()) {
        $transaction->setResultCode(TransactorPluginInterface::RESULT_OK);
      }

      // Sets the execution time and sequence.
      $transaction->setExecutionTime($this->timeService->getRequestTime());
      $transaction->setExecutionSequence($last_executed ? $last_executed->getExecutionSequence() + 1 : 1);

      if (!$executor
        && $this->currentUser
        && $this->currentUser->id()) {
        $executor = User::load($this->currentUser->id());
      }
      $transaction->setExecutor($executor ?: User::getAnonymousUser());

      // Launch the transaction execution event.
      $this->eventDispatcher->dispatch(TransactionExecutionEvent::EVENT_NAME, new TransactionExecutionEvent($transaction));

      // Save the transaction.
      if ($save) {
        $transaction->save();
      }

      $executed = TRUE;
    }
    else {
      // If no result code set by the transactor, set the generic error.
      if (!$transaction->getResultCode()) {
        $transaction->setResultCode(TransactorPluginInterface::RESULT_ERROR);
      }

      $executed = FALSE;
    }

    // Releases the execution lock.
    $this->lock->release($execution_lock_name);

    return $executed;
  }

  /**
   * Locks a transaction for execution.
   *
   * @param \Drupal\transaction\TransactionInterface $transaction
   *   The transaction to lock.
   *
   * @return string|false
   *   The lock name if the transaction where successfully locked for execution,
   *   FALSE if the transaction is locked and the locking timeout was exceeded.
   */
  protected function executionLockAcquire(TransactionInterface $transaction) {
    $lock_name = 'transaction_'
      . $transaction->getTypeId() . '_'
      . $transaction->getTargetEntityId();
    $lock_time = ini_get('max_execution_time') ?: 3600;

    if ($this->lock->acquire($lock_name, $lock_time)
      || (!$this->lock->wait($lock_name) && $this->lock->acquire($lock_name, $lock_time))) {
      return $lock_name;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function composeResultMessage(TransactionInterface $transaction, $langcode = NULL) {
    return $transaction->getType()->getPlugin()->getResultMessage($transaction, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function composeDescription(TransactionInterface $transaction, $langcode = NULL) {
    if ($operation = $transaction->getOperation()) {
      // Description from operation template.
      $token_options = ['clear' => TRUE];
      if ($langcode) {
        $token_options['langcode'] = $langcode;
      }

      $target_entity = $transaction->getTargetEntity();
      $target_entity_type_id = $target_entity->getEntityTypeId();
      $token_data = [
        'transaction' => $transaction,
        TransactorHandler::getTokenContextFromEntityTypeId($target_entity_type_id) => $target_entity,
      ];

      $description = PlainTextOutput::renderFromHtml($this->token->replace($operation->getDescription(), $token_data, $token_options));
    }
    else {
      // Default description from the transactor.
      $description = $transaction->getType()->getPlugin()->getTransactionDescription($transaction, $langcode);
    }

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function composeDetails(TransactionInterface $transaction, $langcode = NULL) {
    // Details from transactor.
    $details = $transaction->getType()->getPlugin()->getTransactionDetails($transaction, $langcode);

    // Details from operation details template.
    if ($operation = $transaction->getOperation()) {
      $token_options = ['clear' => TRUE];
      if ($langcode) {
        $token_options['langcode'] = $langcode;
      }

      $target_entity = $transaction->getTargetEntity();
      $target_entity_type_id = $target_entity->getEntityTypeId();
      $token_data = [
        'transaction' => $transaction,
        TransactorHandler::getTokenContextFromEntityTypeId($target_entity_type_id) => $target_entity,
      ];

      foreach ($operation->getDetails() as $detail) {
        $details[] = PlainTextOutput::renderFromHtml($this->token->replace($detail, $token_data, $token_options));
      }
    }

    return $details;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousTransaction(TransactionInterface $transaction) {
    if ($transaction->isPending()) {
      throw new InvalidTransactionStateException('Cannot get the previously executed transaction to one that is pending execution.');
    }

    $result = $this->transactionStorage->getQuery()
      ->condition('type', $transaction->getTypeId())
      ->condition('target_entity.target_id', $transaction->getTargetEntityId())
      ->condition('target_entity.target_type', $transaction->getType()->getTargetEntityTypeId())
      ->exists('executed')
      ->condition('executed', $transaction->getExecutionTime(), '<')
      ->range(0, 1)
      ->sort('executed', 'DESC')
      ->execute();

    return count($result)
      ? $this->transactionStorage->load(array_pop($result))
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextTransaction(TransactionInterface $transaction) {
    if ($transaction->isPending()) {
      throw new InvalidTransactionStateException('Cannot get the next executed transaction to one that is pending execution.');
    }

    $result = $this->transactionStorage->getQuery()
      ->condition('type', $transaction->getTypeId())
      ->condition('target_entity.target_id', $transaction->getTargetEntityId())
      ->condition('target_entity.target_type', $transaction->getType()->getTargetEntityTypeId())
      ->exists('executed')
      ->condition('executed', $transaction->getExecutionTime(), '>')
      ->range(0, 1)
      ->sort('executed')
      ->execute();

    return count($result)
      ? $this->transactionStorage->load(array_pop($result))
      : NULL;
  }

  /**
   * Guess the token context for a entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The token context for the given entity type ID.
   */
  public static function getTokenContextFromEntityTypeId($entity_type_id) {
    switch ($entity_type_id) {
      case 'taxonomy_term':
        // Taxonomy term token type doesn't match the entity type's machine
        // name.
        $context = 'term';
        break;

      case 'taxonomy_vocabulary':
        // Taxonomy vocabulary token type doesn't match the entity type's
        // machine name.
        $context = 'vocabulary';
        break;

      default:
        $context = $entity_type_id;
        break;
    }

    return $context;
  }

}
