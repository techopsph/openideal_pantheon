<?php

namespace Drupal\domain_registration;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Sets the _admin_route for specific group-related routes.
 */
class DomainRegistrationPattern implements DomainRegistrationPatternInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DomainRegistrationPattern object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    if ($domains = $this->configFactory->get('domain_registration.settings')->get('pattern')) {
      $domains = explode("\r\n", $domains);
    }

    return $domains ?: [];
  }

}
