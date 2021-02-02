<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test the ContentModerationState storage schema.
 *
 * @coversDefaultClass \Drupal\content_moderation\ContentModerationStateStorageSchema
 * @group content_moderation
 */
class ContentModerationStateStorageSchemaTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'content_moderation',
    'user',
    'system',
    'text',
    'workflows',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    NodeType::create([
      'type' => 'example',
    ])->save();

    BlockContentType::create([
      'label' => 'Test',
      'id' => 'example',
    ])->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('block_content', 'example');
    $workflow->save();
  }

  /**
   * Test the ContentModerationState unique keys.
   *
   * @covers ::getEntitySchema
   */
  public function testUniqueKeys() {
    // Create a node which will create a new ContentModerationState entity.
    $node = Node::create([
      'title' => 'Test title',
      'type' => 'example',
      'moderation_state' => 'draft',
    ]);
    $node->save();

    // Ensure an exception when all values match.
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $node->getRevisionId(),
    ], TRUE);

    // No exception for the same values, with a different langcode.
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $node->getRevisionId(),
      'langcode' => 'de',
    ], FALSE);

    // A different workflow should not trigger an exception.
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $node->getRevisionId(),
      'workflow' => 'foo',
    ], FALSE);

    // Different entity types should not trigger an exception.
    $block_content = BlockContent::create([
      'info' => 'Test block',
      'type' => 'example',
      'moderation_state' => 'draft',
    ]);
    $block_content->save();
    $this->assertEquals($block_content->id(), $node->id());
    $this->assertEquals($block_content->getRevisionId(), $node->getRevisionId());

    // Different entity and revision IDs should not trigger an exception.
    $second_node = Node::create([
      'title' => 'Test title',
      'type' => 'example',
      'moderation_state' => 'draft',
    ]);
    $second_node->save();

    // Creating a version of the entity with a previously used, but not current
    // revision ID should trigger an exception.
    $old_revision_id = $node->getRevisionId();
    $node->setNewRevision(TRUE);
    $node->title = 'Updated title';
    $node->moderation_state = 'published';
    $node->save();
    $this->assertStorageException([
      'content_entity_type_id' => $node->getEntityTypeId(),
      'content_entity_id' => $node->id(),
      'content_entity_revision_id' => $old_revision_id,
    ], TRUE);
  }

  /**
   * Assert if a storage exception is triggered when saving a given entity.
   *
   * @param array $values
   *   An array of entity values.
   * @param bool $has_exception
   *   If an exception should be triggered when saving the entity.
   */
  protected function assertStorageException(array $values, $has_exception) {
    $defaults = [
      'moderation_state' => 'draft',
      'workflow' => 'editorial',
    ];
    $entity = ContentModerationState::create($values + $defaults);
    $exception_triggered = FALSE;
    try {
      ContentModerationState::updateOrCreateFromEntity($entity);
    }
    catch (\Exception $e) {
      $exception_triggered = TRUE;
    }
    $this->assertEquals($has_exception, $exception_triggered);
  }

}
