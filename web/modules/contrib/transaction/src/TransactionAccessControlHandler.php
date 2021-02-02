<?php

namespace Drupal\transaction;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the transaction entity.
 *
 * @see \Drupal\transaction\TransactionInterface
 */
class TransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\transaction\TransactionInterface $entity */
    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isForbidden()) {
      return $result;
    }

    if ($operation == 'view label') {
      $operation = 'view';
    }

    // Executed transactions cannot be executed, or updated or deleted by
    // non-admin.
    if (!$entity->isPending() && ($operation == 'execute' || ($operation != 'view' && !$account->hasPermission('administer transactions')))) {
      return AccessResult::forbidden();
    }

    // Having access to the target entity is mandatory.
    if ($target_entity = $entity->getTargetEntity()) {
      $target_result = $entity->getTargetEntity()->access($operation, $account, TRUE);
      if ($target_result->isForbidden()) {
        return $target_result;
      }

      $result = $result->andIf($target_result);
    }

    // At this point, if allowed, user is admin.
    if (!$result->isAllowed()) {
      // Treat view label operation as view.
      if ($operation == 'view label') {
        $operation = 'view';
      }

      // Finally rely on transaction type permissions.
      $type = $entity->getTypeId();
      $result = AccessResult::allowedIfHasPermission($account, "$operation any $type transaction");
      if ($result->isNeutral() && $entity->getOwnerId() == $account->id()) {
        $result = AccessResult::allowedIfHasPermission($account, "$operation own $type transaction");
      }
    }

    return $target_entity ? $result->addCacheableDependency($target_entity) : $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, "create $entity_bundle transaction");
  }

}
