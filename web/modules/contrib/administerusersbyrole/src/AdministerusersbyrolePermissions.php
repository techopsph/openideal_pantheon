<?php

namespace Drupal\administerusersbyrole;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\administerusersbyrole\Services\AccessManagerInterface;

/**
 * Provides dynamic permissions of the administerusersbyrole module.
 */
class AdministerusersbyrolePermissions implements ContainerInjectionInterface {

  /**
   * The access manager.
   *
   * @var \Drupal\administerusersbyrole\Services\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Constructs a new AdministerusersbyrolePermissions instance.
   *
   * @param \Drupal\administerusersbyrole\Services\AccessManagerInterface $access_manager
   *   The entity manager.
   */
  public function __construct(AccessManagerInterface $access_manager) {
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('administerusersbyrole.access'));
  }

  /**
   * Returns an array of administerusersbyrole permissions.
   *
   * @return array
   */
  public function permissions() {
    return $this->accessManager->permissions();
  }

}
