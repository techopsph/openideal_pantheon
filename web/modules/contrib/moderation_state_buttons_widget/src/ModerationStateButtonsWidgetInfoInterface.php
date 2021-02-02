<?php

namespace Drupal\moderation_state_buttons_widget;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface ModerationStateButtonsWidgetInfoInterface.
 */
interface ModerationStateButtonsWidgetInfoInterface {

  /**
   * Returns a list of bundles that can be moderated.
   *
   * @return array
   *   An array with the outer keys in the form of entity type ids. The inner
   *   items provide the 'entity_type' and 'bundles'.
   */
  public function getAllBundlesThatCanBeModerated();

  /**
   * Returns a list of states in the workflow associated with the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The target entity.
   *
   * @return array
   *   A list of states with 'state' and 'transition_possible' keys.
   */
  public function getStates(ContentEntityInterface $entity);

}
