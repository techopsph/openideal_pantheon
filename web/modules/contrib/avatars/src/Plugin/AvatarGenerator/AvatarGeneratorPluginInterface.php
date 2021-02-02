<?php

namespace Drupal\avatars\Plugin\AvatarGenerator;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;

/**
 * Interface for AvatarGenerator plugins.
 */
interface AvatarGeneratorPluginInterface extends PluginInspectionInterface, DerivativeInspectionInterface, PluginFormInterface, ConfigurableInterface, DependentPluginInterface {

  /**
   * Generate a summary about the current configuration of the widget.
   *
   * @return array
   *   A render array.
   */
  public function settingsSummary();

  /**
   * Gets File object for an avatar.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return \Drupal\file\FileInterface
   *   A file object.
   */
  public function getFile(AccountInterface $account);

  /**
   * Creates a URI to an avatar.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return string
   *   URI to an image file.
   */
  public function generateUri(AccountInterface $account);

}
