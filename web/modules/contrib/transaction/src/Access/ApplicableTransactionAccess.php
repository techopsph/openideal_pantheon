<?php

namespace Drupal\transaction\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\transaction\TransactionTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Checks access of applicable entity to transaction type.
 */
class ApplicableTransactionAccess implements AccessInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ApplicableTransactionAccess constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRouteMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Check if the transaction type is applicable to the content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *   (optional) The involved content entity, determined from request if NULL.
   * @param \Drupal\transaction\TransactionTypeInterface|null $transaction_type
   *   (optional) The transaction type, determined from the request or the route
   *   options if NULL.
   * @param \Symfony\Component\Routing\Route|null $route
   *   (optional) The route to check access for.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   (optional) The current request.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allowed if the transaction type is applicable to the content entity.
   */
  public function access(ContentEntityInterface $entity = NULL, TransactionTypeInterface $transaction_type = NULL, Route $route = NULL, Request $request = NULL) {
    // Check access for the current route if none was given.
    if (!$route) {
      $route = $this->currentRouteMatch->getRouteObject();
    }
    if (!$request) {
      $request = $this->requestStack->getCurrentRequest();
    }

    // Try to determine the transaction type if it was not given.
    $transaction_type = $transaction_type ? $transaction_type : $this->guessTransactionType($route, $request);
    if (!$transaction_type) {
      // Unable to determine the transaction type.
      return AccessResult::forbidden();
    }

    // Try to determine the target entity.
    $entity = $entity ? $entity : $this->guessTargetEntity($route, $request);
    if (!$entity) {
      // Unable to determine the target entity.
      return AccessResult::forbidden();
    }

    $result = $transaction_type->isApplicable($entity)
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    return $result
      ->addCacheableDependency($entity)
      ->addCacheableDependency($transaction_type);
  }

  /**
   * Tries to determine the transaction type from request and route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\transaction\TransactionTypeInterface|null
   *   The transaction type, NULL if cannot be determined.
   */
  protected function guessTransactionType(Route $route, Request $request) {
    $transaction_type = $request->get('transaction_type');

    if (!$transaction_type instanceof TransactionTypeInterface) {
      $transaction_type_id = is_string($transaction_type)
        ? $transaction_type
        : $route->getOption('_transaction_transaction_type_id');
      if (!empty($transaction_type_id)) {
        $transaction_type = $this->entityTypeManager
          ->getStorage('transaction_type')
          ->load($transaction_type_id);
      }
    }

    return $transaction_type;
  }

  /**
   * Tries to determine the target entity type from request and route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The target entity type, NULL if cannot be determined.
   */
  protected function guessTargetEntity(Route $route, Request $request) {
    // First try from request argument.
    $entity = $request->get('target_entity');

    if (!$entity instanceof ContentEntityInterface) {
      // Try from request argument named as the target entity type.
      $target_entity_type = $request->get('target_entity_type');

      if ($target_entity_type instanceof EntityTypeInterface) {
        $target_entity_type_id = $target_entity_type->id();
      }
      else {
        $target_entity_type_id = is_string($target_entity_type)
          ? $target_entity_type
          : $route->getOption('_transaction_target_entity_type_id');
      }

      if ($target_entity_type_id) {
        $entity = $request->get($target_entity_type_id) ?: $entity;
        if (is_numeric($entity)) {
          $entity = $this->entityTypeManager->getStorage($target_entity_type_id)
            ->load($entity);
        }
      }
    }

    return $entity instanceof ContentEntityInterface ? $entity : NULL;
  }

}
