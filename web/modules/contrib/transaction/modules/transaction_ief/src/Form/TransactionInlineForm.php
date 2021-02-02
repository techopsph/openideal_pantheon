<?php

namespace Drupal\transaction_ief\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the inline form for the transaction entity.
 */
class TransactionInlineForm extends EntityInlineForm {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs the inline entity form controller.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, EntityTypeInterface $entity_type, RouteMatchInterface $route_match, RequestStack $request_stack) {
    parent::__construct($entity_field_manager, $entity_type_manager, $module_handler, $entity_type);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $entity_type,
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    /** @var \Drupal\transaction\TransactionInterface $transaction */
    $transaction = $entity_form['#entity'];

    // Discover the target entity by examining the current route or by getting
    // from the request argument with the same name as the target entity type.
    if (!$transaction->getTargetEntityId()) {
      $route_options = $this->routeMatch->getRouteObject()->getOptions();
      $target_entity_type_id = isset($route_options['_transaction_target_entity_type_id'])
        ? $route_options['_transaction_target_entity_type_id']
        : $transaction->getType()->getTargetEntityTypeId();
      if ($target_entity = $this->requestStack->getCurrentRequest()->get($target_entity_type_id)) {
        $transaction->setTargetEntity($target_entity);
      }
    }

    return parent::entityForm($entity_form, $form_state);
  }

}
