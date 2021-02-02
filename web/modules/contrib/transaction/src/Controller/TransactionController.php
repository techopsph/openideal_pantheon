<?php

namespace Drupal\transaction\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\transaction\TransactionTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides title callbacks for transaction entities.
 */
class TransactionController extends ControllerBase {

  /**
   * TransactionController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Provides a title callback for transaction collection pages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   (optional) The type of the transactions in the collection.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   (optional) The target entity of the transactions in collection.
   *
   * @return string
   *   The title for the entity collection view page.
   */
  public function transactionCollectionTitle(Request $request, RouteMatchInterface $route_match, TransactionTypeInterface $transaction_type = NULL, ContentEntityInterface $target_entity = NULL) {
    $route_options = $route_match->getRouteObject()->getOptions();
    if (!$transaction_type && isset($route_options['_transaction_transaction_type_id'])) {
      try {
        $transaction_type = $this->entityTypeManager->getStorage('transaction_type')->load($route_options['_transaction_transaction_type_id']);
      }
      catch (InvalidPluginDefinitionException $e) {
        // Continue.
      }
    }

    if (!$target_entity && isset($route_options['_transaction_target_entity_type_id'])) {
      $target_entity = $request->get($route_options['_transaction_target_entity_type_id']);
    }

    if ($target_entity) {
      $title = $transaction_type
        ? $this->t('%type transactions for %target', ['%type' => $transaction_type->label(), '%target' => $target_entity->label()])
        : $this->t('Transactions for %target', ['%target' => $target_entity->label()]);
    }
    else {
      $title = $transaction_type
        ? $this->t('%type transactions', ['%type' => $transaction_type->label()])
        : $this->t('Transactions');
    }
    return $title;
  }

  /**
   * Provides a title callback for transaction creation form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   (optional) The type of the new transaction.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   (optional) The target entity for the new transaction.
   *
   * @return string
   *   The title for the transaction creation form.
   */
  public function transactionAddTitle(RouteMatchInterface $route_match, TransactionTypeInterface $transaction_type = NULL, ContentEntityInterface $target_entity = NULL) {
    if ($target_entity) {
      $title = $transaction_type
        ? $this->t('Create %type transaction for %target', ['%type' => $transaction_type->label(), '%target' => $target_entity->label()])
        : $this->t('Create transaction for %target', ['%target' => $target_entity->label()]);
    }
    else {
      $title = $transaction_type
        ? $this->t('Create %type transaction', ['%type' => $transaction_type->label()])
        : $this->t('Create transaction');
    }
    return $title;
  }

}
