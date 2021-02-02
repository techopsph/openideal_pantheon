<?php

namespace Drupal\Tests\administerusersbyrole\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Testing for administerusersbyrole module.
 *
 * @group administerusersbyrole
 */
class AdministerusersbyroleTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['administerusersbyrole', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected $roles = [];
  protected $users = [];

  /**
   * The access manager.
   *
   * @var \Drupal\administerusersbyrole\Services\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Editable module configuration.
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->accessManager = \Drupal::service('administerusersbyrole.access');
    $this->config = \Drupal::service('config.factory')->getEditable('administerusersbyrole.settings');

    $this->createUserWithRole('noroles', []);
    $this->createRolesAndUsers('alpha', FALSE);
    $this->createRolesAndUsers('beta', TRUE);
    $this->createUserWithRole('alphabeta', ['alpha', 'beta']);

    // alphabeta_ed.
    $perms = [
      'access content',
      $this->accessManager->buildPermString('edit'),
      $this->accessManager->buildPermString('edit', 'alpha'),
      $this->accessManager->buildPermString('edit', 'beta'),
    ];
    $this->drupalCreateRole($perms, 'alphabeta_ed');
    $this->config->set("roles.alphabeta_ed", 'perm')->save();
    $this->createUserWithRole('alphabeta_ed', ['alphabeta_ed']);

    // all_editor.
    $perms = [
      'access content',
      $this->accessManager->buildPermString('edit'),
    ];
    foreach (array_keys($this->accessManager->managedRoles()) as $roleName) {
      $perms[] = $this->accessManager->buildPermString('edit', $roleName);
    }
    $this->drupalCreateRole($perms, 'all_editor');
    $this->config->set("roles.all_editor", 'perm')->save();
    $this->createUserWithRole('all_editor', ['all_editor']);

    // all_deletor.
    $perms = [
      'access content',
      $this->accessManager->buildPermString('cancel'),
    ];
    foreach (array_keys($this->accessManager->managedRoles()) as $roleName) {
      $perms[] = $this->accessManager->buildPermString('cancel', $roleName);
    }
    $this->drupalCreateRole($perms, 'all_deletor');
    $this->createUserWithRole('all_deletor', ['all_deletor']);

    // Creator.
    $perms = [
      'access content',
      'create users',
    ];
    $this->drupalCreateRole($perms, 'creator');
    $this->createUserWithRole('creator', ['creator']);
  }

  /**
   * Tests basic permissions.
   */
  public function testPermissions() {
    $expectations = [
      // When I'm logged in as...
      'nobody' => [
        // ...I can perform these actions on this other user...
        'noroles'      => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha'        => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => FALSE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'noroles' => [
        'noroles'      => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha'        => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => FALSE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'alpha' => [
        'noroles'      => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha'        => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => FALSE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'alpha_editor' => [
        'noroles'      => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha'        => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => TRUE, 'cancel' => FALSE],
        'beta'         => ['edit' => FALSE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'beta' => [
        'noroles'      => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha'        => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => TRUE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'beta_editor' => [
        'noroles'      => ['edit' => TRUE, 'cancel' => TRUE],
        'alpha'        => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => TRUE, 'cancel' => TRUE],
        'beta_editor'  => ['edit' => TRUE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'alphabeta' => [
        'noroles'      => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha'        => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => FALSE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => TRUE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'alphabeta_ed' => [
        'noroles'      => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha'        => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => TRUE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => TRUE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => TRUE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'all_editor' => [
        'noroles'      => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha'        => ['edit' => TRUE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => TRUE, 'cancel' => FALSE],
        'beta'         => ['edit' => TRUE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => TRUE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => TRUE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => TRUE, 'cancel' => FALSE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => TRUE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'all_deletor' => [
        'noroles'      => ['edit' => FALSE, 'cancel' => TRUE],
        'alpha'        => ['edit' => FALSE, 'cancel' => TRUE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => TRUE],
        'beta'         => ['edit' => FALSE, 'cancel' => TRUE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => TRUE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => TRUE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => TRUE],
        'creator'      => ['edit' => FALSE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => TRUE],
        'all_deletor'  => ['edit' => TRUE, 'cancel' => FALSE],
        'create users' => FALSE,
      ],
      'creator' => [
        'noroles'      => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha'        => ['edit' => FALSE, 'cancel' => FALSE],
        'alpha_editor' => ['edit' => FALSE, 'cancel' => FALSE],
        'beta'         => ['edit' => FALSE, 'cancel' => FALSE],
        'beta_editor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta'    => ['edit' => FALSE, 'cancel' => FALSE],
        'alphabeta_ed' => ['edit' => FALSE, 'cancel' => FALSE],
        'creator'      => ['edit' => TRUE, 'cancel' => FALSE],
        'all_editor'   => ['edit' => FALSE, 'cancel' => FALSE],
        'all_deletor'  => ['edit' => FALSE, 'cancel' => FALSE],
        'create users' => TRUE,
      ],
    ];

    $assert = $this->assertSession();
    foreach ($expectations as $loginUsername => $editUsernames) {
      if ($loginUsername !== 'nobody') {
        $this->drupalLogin($this->users[$loginUsername]);
      }

      foreach ($editUsernames as $k => $v) {
        if ($k === 'create users') {
          $this->drupalGet("admin/people/create");
          $expectedResult = $v;
          if ($expectedResult) {
            $this->assertRaw('<h1 class="page-title">Add user</h1>');
          }
          else {
            $this->assertRaw('You are not authorized to access this page.');
          }
        }
        else {
          $editUsername = $k;
          $operations = $v;
          $editUid = $this->users[$editUsername]->id();
          foreach ($operations as $operation => $expectedResult) {
            $this->drupalGet("user/$editUid/$operation");

            if ($expectedResult) {
              if ($operation === 'edit') {
                $assert->responseContains("All emails from the system will be sent to this address.");
              }
              elseif ($operation === 'cancel') {
                $assert->responseContains("Are you sure you want to cancel the account <em class=\"placeholder\">$editUsername</em>?");
              }
            }
            else {
              $content = $this->getSession()->getPage()->getContent();
              $denied = strstr($content, "You do not have permission to $operation <em class=\"placeholder\">$editUsername</em>.") || strstr($content, 'Access denied');
              $this->assertTrue($denied, "My expectation is that $loginUsername shouldn't be able to $operation $editUsername, but it can.");
            }
          }
        }
      }

      if ($loginUsername !== 'nobody') {
        $this->drupalLogout();
      }
    }
  }

  /**
   * Creates a user with the specified name and roles.
   */
  protected function createUserWithRole($userName, $roleNames) {
    $user = $this->drupalCreateUser([], $userName);
    $this->assertNotEmpty($user, "Unable to create user $userName.");
    foreach ($roleNames as $role) {
      $user->addRole($role);
    }
    $user->save();
    $this->users[$userName] = $user;
  }

  /**
   * Creates and role, a user with that roles and a user that can edit the
   * role.
   */
  protected function createRolesAndUsers($roleName, $allowEditorToCancel) {
    // Create basic role.
    $this->drupalCreateRole(['access content'], $roleName);
    $this->config->set("roles.$roleName", 'perm')->save();
    $this->createUserWithRole($roleName, [$roleName]);

    // Create role to edit above role and also anyone with no custom roles.
    $perms = [
      'access content',
      $this->accessManager->buildPermString('edit'),
      $this->accessManager->buildPermString('edit', $roleName),
    ];
    if ($allowEditorToCancel) {
      $perms[] = $this->accessManager->buildPermString('cancel');
      $perms[] = $this->accessManager->buildPermString('cancel', $roleName);
    }
    $this->drupalCreateRole($perms, "{$roleName}_editor");
    $this->config->set("roles.{$roleName}_editor", 'perm')->save();
    $this->createUserWithRole("{$roleName}_editor", ["{$roleName}_editor"]);
  }

}
