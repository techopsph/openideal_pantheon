<?php

namespace Drupal\transaction;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for transactions of different types.
 */
class TransactionPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MediaPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of transaction type permissions.
   *
   * @return array
   *   The transaction type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function transactionTypePermissions() {
    $perms = [];

    // Generate transaction permissions for all transaction types.
    $transaction_types = $this->entityTypeManager
      ->getStorage('transaction_type')->loadMultiple();
    foreach ($transaction_types as $transaction_type) {
      $perms += $this->buildPermissions($transaction_type);
    }

    return $perms;
  }

  /**
   * Returns a list of transaction permissions for a given transaction type.
   *
   * @param \Drupal\transaction\TransactionTypeInterface $type
   *   The transaction type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(TransactionTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id transaction" => [
        'title' => $this->t('%type_name: Create new transaction', $type_params),
      ],
      "view own $type_id transaction" => [
        'title' => $this->t('%type_name: View own transaction', $type_params),
      ],
      "view any $type_id transaction" => [
        'title' => $this->t('%type_name: View any transaction', $type_params),
      ],
      "edit own $type_id transaction" => [
        'title' => $this->t('%type_name: Edit own transaction', $type_params),
      ],
      "edit any $type_id transaction" => [
        'title' => $this->t('%type_name: Edit any transaction', $type_params),
      ],
      "delete own $type_id transaction" => [
        'title' => $this->t('%type_name: Delete own transaction', $type_params),
      ],
      "delete any $type_id transaction" => [
        'title' => $this->t('%type_name: Delete any transaction', $type_params),
      ],
      "execute own $type_id transaction" => [
        'title' => $this->t('%type_name: Execute own transaction', $type_params),
      ],
      "execute any $type_id transaction" => [
        'title' => $this->t('%type_name: Execute any transaction', $type_params),
      ],
    ];
  }

}
