<?php

namespace Drupal\administerusersbyrole\Services;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Access Manager.
 */
class AccessManager implements AccessManagerInterface {

  use StringTranslationTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */

  protected $config;

  const CONVERT_OP = [
    'cancel' => 'cancel',
    'delete' => 'cancel',
    'edit' => 'edit',
    'update' => 'edit',
    'view' => 'view',
    'role-assign' => 'role-assign',
  ];

  /**
   * Constructs a new AccessManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->config = $config_factory->get('administerusersbyrole.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function rolesChanged() {
    $role_config = [];
    foreach (array_keys($this->managedRoles()) as $rid) {
      $role_config[$rid] = $this->config->get("roles.$rid") ?: self::UNSAFE;
    }

    $this->configFactory->getEditable('administerusersbyrole.settings')->set('roles', $role_config)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    // Base permissions.
    $op_titles = [
      'edit' => $this->t('Edit users with allowed roles'),
      'cancel' => $this->t('Cancel users with allowed roles'),
      'view' => $this->t('View users with allowed roles'),
      'role-assign' => $this->t('Assign allowed roles'),
    ];

    foreach ($op_titles as $op => $title) {
      $perm_string = $this->buildPermString($op);
      $perms[$perm_string] = ['title' => $title];
    }

    // Per role permissions.
    $role_config = $this->config->get('roles') ?: [];
    $role_config = array_filter($role_config, function ($s) {
      return $s == self::PERM;
    });
    $roles = array_intersect_key($this->managedRoles(), $role_config);

    foreach ($roles as $rid => $role) {
      // Use a non-breaking space here to adjust the order so that these come
      // after the base permission.
      $op_role_titles = [
        'edit' => $this->t("Edit users\u{00A0}includes role %role", ['%role' => $role->label()]),
        'cancel' => $this->t("Cancel users\u{00A0}includes role %role", ['%role' => $role->label()]),
        'view' => $this->t("View users\u{00A0}includes role %role", ['%role' => $role->label()]),
        'role-assign' => $this->t("Assign roles includes role %role", ['%role' => $role->label()]),
      ];


      foreach ($op_role_titles as $op => $title) {
        $perm_string = $this->buildPermString($op, $rid);
        $description = $this->t('This permission only works when combined with %base', ['%base' => $op_titles[$op]]);
        $perms[$perm_string] = ['title' => $title, 'description' => $description];
      }
    }

    return $perms;
  }

  /**
   * {@inheritdoc}
   */
  public function access(array $roles, $operation, AccountInterface $account) {
    if (!$this->preAccess($operation, $account)) {
      return AccessResult::neutral();
    }

    foreach ($roles as $rid) {
      if (!$this->roleAccess($operation, $account, $rid)) {
        return AccessResult::neutral();
      }
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function listRoles($operation, AccountInterface $account) {
    if (!$this->preAccess($operation, $account)) {
      return [];
    }

    $roles = [AccountInterface::AUTHENTICATED_ROLE];
    foreach (array_keys($this->managedRoles()) as $rid) {
      if ($this->roleAccess($operation, $account, $rid)) {
        $roles[] = $rid;
      }
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function managedRoles() {
    $roles = array_filter(user_roles(TRUE), function ($role) {
      return !$role->hasPermission('administer users');
    });
    unset($roles[AccountInterface::AUTHENTICATED_ROLE]);
    return $roles;
  }

  /**
   * Initial access check for an operation to test if access might be granted for some roles.
   *
   * @param string $operation
   *   The operation that is to be performed on the user.
   *   Value is updated to match the canonical value used in this module.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result. hook_entity_access() has detailed documentation.
   */
  protected function preAccess(&$operation, AccountInterface $account) {
    // Full admins already have permissions so we are wasting our time to continue.
    if ($account->hasPermission('administer users')) {
      return FALSE;
    }

    // Ignore unrecognised operation.
    if (!array_key_exists($operation, self::CONVERT_OP)) {
      return FALSE;
    }

    // Check the base permission.
    $operation = self::CONVERT_OP[$operation];
    return $this->hasPerm($operation, $account);
  }

  /**
   * Checks access for a given role.
   */
  protected function roleAccess($operation, AccountInterface $account, $rid) {
    if ($rid == AccountInterface::AUTHENTICATED_ROLE) {
      return self::SAFE;
    }

    $setting = $this->config->get("roles.$rid") ?: self::UNSAFE;
    switch ($setting) {
      case self::SAFE:
        return TRUE;

      case self::UNSAFE:
        return FALSE;

      case self::PERM:
        return $this->hasPerm($operation, $account, $rid);
    }
  }

  /**
   * Checks access to a permission for a given role.
   */
  protected function hasPerm($operation, AccountInterface $account, $rid = NULL) {
    return $account->hasPermission($this->buildPermString($operation, $rid));
  }

  /**
   * Generates a permission string for a given role.
   */
  public function buildPermString($operation, $rid = NULL) {
    return $rid ? "$operation users with role $rid" : "$operation users by role";
  }

}
