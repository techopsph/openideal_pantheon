<?php

namespace Drupal\administerusersbyrole\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Common overrides for AddRoleUser and RemoveRoleUser.
 */
trait ChangeUserRoleTrait {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = parent::access($object, $account, TRUE)
      ->orIf(administerusersbyrole_user_assign_role($object, $account, [$this->configuration['rid']]));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $allowed = \Drupal::service('administerusersbyrole.access')->listRoles('role-assign', \Drupal::currentUser());
    $form['rid']['#options'] = array_intersect_key($form['rid']['#options'], array_flip($allowed));
    return $form;
  }

}
