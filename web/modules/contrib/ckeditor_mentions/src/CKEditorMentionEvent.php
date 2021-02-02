<?php

namespace Drupal\ckeditor_mentions;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CKEditorMentionEvent.
 *
 * @package Drupal\ckeditor_mentions
 */
class CKEditorMentionEvent extends Event {

  const MENTION_FIRST = 'event.mention';
  const MENTION_SUBSEQUENT = 'event.mention_subsequent';

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The users mentioned in the entity.
   *
   * @var array
   */
  protected $mentionedUsers;

  /**
   * CKEditorMentionEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the event.
   * @param array $mentioned_users
   *   The users mentioned in the entity.
   */
  public function __construct(EntityInterface $entity, array $mentioned_users = []) {
    $this->entity = $entity;
    $this->mentionedUsers = $mentioned_users;
  }

  /**
   * Returns the reference ID.
   *
   * @return string
   *   The reference Id.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that triggered the event.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Returns an array with the mentioned users.
   *
   * @return array
   *   An array with the mentioned users.
   */
  public function getMentionedUsers() {
    return $this->mentionedUsers;
  }

  /**
   * Sets the list of the mentioned users.
   *
   * @param array $mentioned_users
   *   The mentioned users.
   */
  public function setMentionedUsers(array $mentioned_users = []) {
    $this->mentionedUsers = $mentioned_users;
  }

}
