<?php

namespace Drupal\group_roles_management\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Permissions controller.
 */
class PermissionsController extends ControllerBase {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Returns custom group permissions.
   *
   * @return array
   *   Permissions.
   */
  public function permissions() {
    $group_type = $this->routeMatch->getParameter('group_type');
    if (!empty($group_type)) {
      $group = $this->routeMatch->getParameter('group');
      if (!empty($group)) {
        $group_type = $group->getGroupType();
      }
    }

    $permissions = [];
    if (!empty($group_type)) {
      foreach ($group_type->getRoles(FALSE) as $role_id => $role_definition) {
        $permissions["manage members with role {$role_id}"] = [
          'title' => "Manage members with role {$role_definition->label()}",
          'section' => $group_type->label(),
        ];
      }
    }

    return $permissions;
  }

}
