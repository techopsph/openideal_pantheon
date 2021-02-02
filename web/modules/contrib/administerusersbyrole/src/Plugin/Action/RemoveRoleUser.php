<?php

namespace Drupal\administerusersbyrole\Plugin\Action;

use Drupal\user\Plugin\Action\RemoveRoleUser as RemoveRoleUserBase;

/**
 * Alternative implementation for Action id = "user_remove_role_action".
 */
class RemoveRoleUser extends RemoveRoleUserBase {

  use ChangeUserRoleTrait;

}
