<?php

namespace Drupal\group_roles_management\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Component\Utility\Html;

/**
 * Only shows the group roles which are available for a group type.
 *
 * The only handler setting is 'group_type_id', a required string that points
 * to the ID of the group type for which this handler will be run.
 *
 * @EntityReferenceSelection(
 *   id = "group_type:group_role",
 *   label = @Translation("Group type role selection"),
 *   entity_types = {"group_role"},
 *   group = "group_type",
 *   weight = 0
 * )
 */
class GroupTypeRoleSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $group_type_id = $this->configuration['handler_settings']['group_type_id'];

    $query = parent::buildEntityQuery($match, $match_operator);
    $query->condition('group_type', $group_type_id, '=');
    $query->condition('internal', 0, '=');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    // Check permissions.
    $account = $this->currentUser;
    $group_content = $this->getConfiguration()['entity'];
    $group = $group_content->getGroup();
    $current_roles = [];
    foreach ($group_content->group_roles->getIterator() as $role) {
      $current_roles[] = $role->getValue()['target_id'];
    }

    // Only filter of is not group creation membership wizard.
    if ($group) {
      foreach ($result as $role_id) {
        if (!in_array($role_id, $current_roles) && !$group->hasPermission("manage members with role {$role_id}", $account)) {
          unset($result[$role_id]);
        }
      }
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityManager->getTranslationFromContext($entity)->label());
    }

    return $options;
  }

}
