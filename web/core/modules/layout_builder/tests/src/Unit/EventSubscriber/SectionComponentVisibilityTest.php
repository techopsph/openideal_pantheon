<?php

namespace Drupal\Tests\layout_builder\Unit\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Uuid\Php as UuidFactory;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextHandler;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\EventSubscriber\SectionComponentVisibility;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;

/**
 * Covers class responsible for section component visibility.
 *
 * @group layout_builder
 * @coversDefaultClass \Drupal\layout_builder\EventSubscriber\SectionComponentVisibility
 */
class SectionComponentVisibilityTest extends UnitTestCase {

  protected $conditionManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->conditionManager = new ConditionManager(
      new \ArrayIterator([]),
      new NullBackend('null'),
      $this->prophesize(ModuleHandlerInterface::class)->reveal()
    );
  }

  /**
   * Ensure that nothing happens when previewing.
   *
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderPreview() {
    $subscriber = new SectionComponentVisibility(
      new ContextHandler(),
      $this->conditionManager
    );
    $event = $this->prophesize(SectionComponentBuildRenderArrayEvent::class);
    // Assert that when not in preview, the event is ignored. We do this by
    // asserting that in preview is called and nothing else.
    $event->inPreview()
      ->shouldBeCalled();

    $component = new SectionComponent('', 'top');
    $event->getComponent()
      ->willReturn($component)
      ->shouldBeCalled();
    $subscriber->onBuildRender($event->reveal());
  }

  /**
   * Ensure no conditions are applied when visibility isn't set.
   *
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderNonPreviewEmpty() {
    $subscriber = new SectionComponentVisibility(
      new ContextHandler(),
      $this->conditionManager
    );
    $event = $this->prophesize(SectionComponentBuildRenderArrayEvent::class);
    // We're not in a preview.
    $event->inPreview()
      ->willReturn(FALSE);

    $component = new SectionComponent('', 'top');
    $event->getComponent()
      ->shouldBeCalled()
      ->willReturn($component);
    $subscriber->onBuildRender($event->reveal());
  }

  /**
   * Ensure no conditions are applied when visibility isn't set.
   *
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderNonPreviewBadPlugin() {
    $subscriber = new SectionComponentVisibility(
      new ContextHandler(),
      $this->conditionManager
    );
    $event = $this->prophesize(SectionComponentBuildRenderArrayEvent::class);
    // We're not in a preview.
    $event->inPreview()
      ->willReturn(FALSE);

    // Build a component so we can set properties.
    $component = new SectionComponent('', 'top');
    $component->set('visibility', [
      'uuid' => ['id' => 'plugin_dne'],
    ]);
    $event->getComponent()
      ->willReturn($component);

    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "plugin_dne" plugin does not exist.');
    $subscriber->onBuildRender($event->reveal());
  }

  /**
   * Ensure context aware plugins get their context applied.
   *
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderNonPreviewResolveContextAware() {
    // Mock context aware plugin that will be used to assert we recieve context.
    $context_aware_plugin = $this->prophesize(ConditionInterface::class)
      ->willImplement(ContextAwarePluginInterface::class);

    // Set our mock condition manager to return the context plugin.
    $condition_manager = $this->prophesize(ConditionManager::class);
    $condition_manager->createInstance('context_aware', ['id' => 'context_aware'])
      ->shouldBeCalledTimes(1)
      ->willReturn($context_aware_plugin->reveal());
    $event = $this->prophesize(SectionComponentBuildRenderArrayEvent::class);
    // We're not in a preview.
    $event->inPreview()
      ->willReturn(FALSE);

    // Build a component with our "context_aware" plugin in the visibility.
    $component = new SectionComponent('', 'top');
    $component->set('visibility', [
      'uuid' => ['id' => 'context_aware'],
    ]);
    $event->getComponent()
      ->willReturn($component);

    // Setup a context array.
    $context_definition = new ContextDefinition();
    $context_data = StringData::createInstance(DataDefinition::create('string'));
    $context_data->setValue('foo');
    $context = [
      'bar' => new Context($context_definition, $context_data),
    ];

    // Make sure the context is returned by the event.
    $event->getContexts()->willReturn($context);

    // Steps that will be called in the process of resolving context.
    $event->addCacheableDependency($context_aware_plugin)
      ->shouldBeCalledTimes(1);
    $context_aware_plugin->setContext('bar', $context['bar'])
      ->shouldBeCalledTimes(1);
    $context_aware_plugin->execute()->willReturn(TRUE);
    $context_aware_plugin->getContextMapping()->willReturn([]);
    $context_aware_plugin->getContext('bar')->willReturn(NULL);
    // We want the "bar" context.
    $context_aware_plugin->getContextDefinitions()
      ->willReturn(['bar' => $context_definition]);

    // Run the onBuildRender handler so things run.
    (new SectionComponentVisibility(
      new ContextHandler(),
      $condition_manager->reveal()
    ))->onBuildRender($event->reveal());
  }

  /**
   * Ensure visibility plugins control event propagation.
   *
   * @covers ::onBuildRender
   * @dataProvider buildRenderResolves
   */
  public function testOnBuildRenderNonPreviewResolve($result, $context_results) {
    $uuid_factory = new UuidFactory();
    $visibility_def = [];

    $event = $this->prophesize(SectionComponentBuildRenderArrayEvent::class);

    // Set our mock condition manager to return the context plugin.
    $condition_manager = $this->prophesize(ConditionManager::class);
    foreach ($context_results as $plugin_id => $plugin_result) {
      $plugin = $this->prophesize(ConditionInterface::class);
      $condition_manager->createInstance($plugin_id, ['id' => $plugin_id])
        ->willReturn($plugin->reveal());
      $plugin->execute()->willReturn($plugin_result);
      $visibility_def[$uuid_factory->generate()] = ['id' => $plugin_id];
      $event->addCacheableDependency($plugin)->shouldBeCalled();
    }

    // We're not in a preview.
    $event->inPreview()
      ->willReturn(FALSE);

    // Build a component with our "context_aware" plugin in the visibility.
    $component = new SectionComponent('', 'top');
    $component->set('visibility', $visibility_def);
    $event->getComponent()
      ->willReturn($component);

    // Assert different propagation outcomes.
    if ($result) {
      $event->stopPropagation()->shouldNotBeCalled();
    }
    else {
      $event_plugin = $this->prophesize(PluginInspectionInterface::class)->reveal();
      $event->stopPropagation()->shouldBeCalledTimes(1);
      $event->getPlugin()->willReturn($event_plugin);
    }

    // Run the onBuildRender handler so things run.
    (new SectionComponentVisibility(
      new ContextHandler(),
      $condition_manager->reveal()
    ))->onBuildRender($event->reveal());

  }

  /**
   * Data Provider for testOnBuildRenderNonPreviewResolve().
   *
   * @return array
   *   Method parameters for testOnBuildRenderNonPreviewResolve().
   */
  public function buildRenderResolves() {
    return [
      [TRUE, ['foo' => TRUE, 'bar' => TRUE]],
      [FALSE, ['foo' => TRUE, 'bar' => FALSE]],
      [FALSE, ['foo' => TRUE, 'bar' => FALSE, 'biz' => TRUE]],
    ];
  }

}
