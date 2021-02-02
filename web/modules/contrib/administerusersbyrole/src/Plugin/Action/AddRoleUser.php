<?php

namespace Drupal\administerusersbyrole\Plugin\Action;

use Drupal\user\Plugin\Action\AddRoleUser as AddRoleUserBase;

/**
 * Alternative implementation for Action id = "user_add_role_action".
 */
class AddRoleUser extends AddRoleUserBase {

  use ChangeUserRoleTrait;

}
