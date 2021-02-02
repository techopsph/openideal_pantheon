<?php

namespace Drupal\Tests\transaction\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for functional tests of the Transaction module.
 */
abstract class FunctionalTransactionTestBase extends BrowserTestBase {

  /**
   * The default theme for browser tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_ui',
    'block',
    'filter',
    'text',
    'entity_test',
    'dynamic_entity_reference',
    'transaction',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a basic bundle on the entity test type.
    entity_test_create_bundle('basic', 'Basic');
  }

}
