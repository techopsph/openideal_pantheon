<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Event\ContentModerationStateChangedEvent;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests events are properly fired during create/save of moderation states.
 *
 * @group content_moderation
 */
class ContentModerationStateChangedEventTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'language',
    'content_translation',
    'content_moderation',
    'user',
    'workflows',
    'node',
  ];

  /**
   * A mock event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('workflows');
    $this->installSchema('node', ['node_access']);
    $this->installConfig('content_moderation');

    NodeType::create([
      'title' => 'Test node',
      'type' => 'example',
    ])->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
    $this->container->set('event_dispatcher', $this->eventDispatcher);

    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Tests events for adding and updating moderation state entities.
   */
  public function testCreateUpdateStates() {

    $this->assertEventDispatchedAtIndex(0, function($event_name, ContentModerationStateChangedEvent $event) {
      $this->assertEquals('content_moderation.state_changed', $event_name);
      $this->assertEquals('editorial', $event->getWorkflow());
      $this->assertEquals(FALSE, $event->getOriginalState());
      $this->assertEquals('draft', $event->getNewState());
      $this->assertEquals('node', $event->getModeratedEntity()->getEntityTypeId());
      $this->assertEquals(1, $event->getModeratedEntity()->getRevisionId());
    });

    $this->assertEventDispatchedAtIndex(1, function($event_name, ContentModerationStateChangedEvent $event) {
      $this->assertEquals('content_moderation.state_changed', $event_name);
      $this->assertEquals('editorial', $event->getWorkflow());
      $this->assertEquals('draft', $event->getOriginalState());
      $this->assertEquals('published', $event->getNewState());
      $this->assertEquals('node', $event->getModeratedEntity()->getEntityTypeId());
      $this->assertEquals(2, $event->getModeratedEntity()->getRevisionId());
    });

    $this->assertEventDispatchedAtIndex(2, function($event_name, ContentModerationStateChangedEvent $event) {
      $this->assertEquals('content_moderation.state_changed', $event_name);
      $this->assertEquals('editorial', $event->getWorkflow());
      $this->assertEquals('published', $event->getOriginalState());
      $this->assertEquals('archived', $event->getNewState());
      $this->assertEquals('node', $event->getModeratedEntity()->getEntityTypeId());
      $this->assertEquals(3, $event->getModeratedEntity()->getRevisionId());
    });

    $this->assertEventDispatchedAtIndex(3, function($event_name, ContentModerationStateChangedEvent $event) {
      $this->assertEquals('content_moderation.state_changed', $event_name);
      $this->assertEquals('editorial', $event->getWorkflow());
      $this->assertEquals('archived', $event->getOriginalState());
      $this->assertEquals('published', $event->getNewState());
      $this->assertEquals('node', $event->getModeratedEntity()->getEntityTypeId());
      $this->assertEquals(4, $event->getModeratedEntity()->getRevisionId());
      $this->assertEquals('fr', $event->getModeratedEntity()->language()->getId());
    });

    $this->assertEventDispatchedAtIndex(4, function($event_name, ContentModerationStateChangedEvent $event) {
      $this->assertEquals('content_moderation.state_changed', $event_name);
      $this->assertEquals('editorial', $event->getWorkflow());
      $this->assertEquals('published', $event->getOriginalState());
      $this->assertEquals('draft', $event->getNewState());
      $this->assertEquals('node', $event->getModeratedEntity()->getEntityTypeId());
      $this->assertEquals(5, $event->getModeratedEntity()->getRevisionId());
      $this->assertEquals('fr', $event->getModeratedEntity()->language()->getId());
    });

    $node = Node::create([
      'type' => 'example',
      'title' => 'Foo',
      'moderation_state' => 'draft',
    ]);
    $node->save();

    $node->moderation_state = 'published';
    $node->save();

    $node->moderation_state = 'archived';
    $node->save();

    $french_node = $node->addTranslation('fr');
    $french_node->title = 'French node';
    $french_node->moderation_state = 'published';
    $french_node->save();

    $french_node->moderation_state = 'draft';
    $french_node->save();
  }

  /**
   * Assert the event information dispatched at a particular index.
   *
   * @param int $index
   *   The index.
   * @param callable $callback
   *   A callback passed two arguments, the event name and event.
   */
  protected function assertEventDispatchedAtIndex($index, $callback) {
    $this->eventDispatcher
      ->expects($this->at($index))
      ->method('dispatch')
      ->willReturnCallback($callback);
  }

}
