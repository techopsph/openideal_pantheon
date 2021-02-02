<?php

namespace Drupal\content_moderation\Event;

/**
 * Defines events that content_moderation dispatches.
 *
 * @see \Drupal\content_moderation\Event\ContentModerationStateChangedEvent
 */
final class ContentModerationEvents {

  /**
   * Name of the event fired when content changes state.
   *
   * @see \Drupal\content_moderation\Event\ContentModerationStateChangedEvent
   * @see \Drupal\content_moderation\Entity\ContentModerationState::realSave()
   */
  const STATE_CHANGED = 'content_moderation.state_changed';

}
