<?php

namespace Drupal\domain_registration;

/**
 * Defines an interface for the domain registration service.
 */
interface DomainRegistrationPatternInterface {

  /**
   * Returns an array of domain patterns based on the configuration.
   *
   * This is done through a service so other modules can decorate it and provide
   * alternative sources of domains.
   *
   * @return array
   *   List of domain patterns.
   */
  public function getPatterns();

}
