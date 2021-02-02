<?php

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Determines component visibility.
 */
class SectionComponentVisibility implements EventSubscriberInterface {

  use ConditionAccessResolverTrait;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionManager;

  /**
   * Creates a SectionComponentVisibility object.
   *
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The condition plugin manager.
   */
  public function __construct(ContextHandlerInterface $context_handler, ExecutableManagerInterface $condition_manager) {
    $this->contextHandler = $context_handler;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority is set to 255 so this subscriber is run after the one in
    // BlockComponentRenderArray.
    // @see \Drupal\layout_builder\EventSubscriber::getSubscribedEvents().
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender', 255];
    return $events;
  }

  /**
   * Determines the visibility of section components.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component build render array event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $conditions = [];
    if (!$event->inPreview()) {
      $visibility = $event->getComponent()->get('visibility') ?: [];
      foreach ($visibility as $uuid => $configuration) {
        $condition = $this->conditionManager->createInstance($configuration['id'], $configuration);
        if ($condition instanceof ContextAwarePluginInterface) {
          $this->contextHandler->applyContextMapping($condition, $event->getContexts());
        }
        $event->addCacheableDependency($condition);
        $conditions[$uuid] = $condition;
      }
    }

    $visibility_operator = $event->getComponent()->get('visibility_operator') ?: 'and';

    if ($conditions && !$this->resolveConditions($conditions, $visibility_operator)) {
      // If conditions do not resolve, do not process other subscribers.
      $event->stopPropagation();
    }
  }

}
