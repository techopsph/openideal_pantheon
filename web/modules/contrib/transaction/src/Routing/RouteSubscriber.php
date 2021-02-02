<?php

namespace Drupal\transaction\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for transaction routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add transaction collection route for transaction types with a link
    // template for it.
    foreach ($this->entityTypeManager->getStorage('transaction_type')->loadMultiple() as $transaction_type_id => $transaction_type) {
      /** @var \Drupal\transaction\TransactionTypeInterface $transaction_type */
      if ($route_path = $this->entityTypeManager->getDefinition($target_entity_type_id = $transaction_type->getTargetEntityTypeId())->getLinkTemplate("transaction-$transaction_type_id")) {
        $route = new Route($route_path);
        $route
          ->addDefaults([
            '_entity_list' => 'transaction',
            '_title' => 'Transactions',
            '_title_callback' => '\Drupal\transaction\Controller\TransactionController::transactionCollectionTitle',
          ])
          ->addRequirements([
            '_permission' => "view any $transaction_type_id transaction",
            '_entity_access' => "$target_entity_type_id.view",
            $target_entity_type_id => '\d+',
            '_applicable_transaction_type' => 'TRUE',
          ])
          ->setOption('_admin_route', TRUE)
          ->setOption('_transaction_transaction_type_id', $transaction_type_id)
          ->setOption('_transaction_target_entity_type_id', $target_entity_type_id)
          ->setOption('parameters', [
            $target_entity_type_id => ['type' => 'entity:' . $target_entity_type_id],
            'transaction_type' => ['type' => 'entity:transaction_type'],
          ]);

        $collection->add("entity.$target_entity_type_id.$transaction_type_id-transaction", $route);

        // Transaction creation route variant. The name of the target entity
        // parameter must match the target entity id.
        $route = clone $collection->get('entity.transaction.add_form');
        $route
          ->setPath("/transaction/add/{transaction_type}/{target_entity_type}/{{$target_entity_type_id}}")
          ->setOption('_transaction_target_entity_type_id', $target_entity_type_id);
        $route_options = $route->getOptions();
        unset($route_options['parameters']['target_entity']);
        $route_options['parameters'][$target_entity_type_id]['type'] = 'entity:' . $target_entity_type_id;
        $route->setOptions($route_options);
        $route_requirements = $route->getRequirements();
        $route_requirements['_entity_access'] = $target_entity_type_id . '.view';
        $route_requirements[$target_entity_type_id] = '\d+';
        unset($route_requirements['target_entity']);
        $route->setRequirements($route_requirements);

        $collection->add("entity.transaction.$target_entity_type_id.$transaction_type_id.add_form", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }

}
