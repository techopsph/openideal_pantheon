<?php

/**
 * @file Contains the code to generate the drush commands.
 */

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;

/**
 * Implements hook_drush_command().
 */
function config_pages_drush_command() {
  $items = [];

  $items['config-pages-set-field-value'] = [
    'description' => 'Set a value for the field of Config Pages',
    'arguments' => [
      'bundle' => 'The type of config page "/admin/structure/config_pages/types"',
      'field_name' => 'The name of field',
      'value' => 'The value for the field',
    ],
    'options' => [
      'append' => 'Append to an existing value.',
    ],
    'drupal dependencies' => ['config_pages'],
    'aliases' => ['cpsfv'],
    'examples' => [
      'drush cpsfv bundle field_name value' => 'Set new value for field_name.',
      'drush cpsfv bundle field_name value --append' => 'Append a value to existing string.',
    ],
  ];

  $items['config-pages-get-field-value'] = [
    'description' => 'Get a value for the field of Config Pages',
    'arguments' => [
      'bundle' => 'The type of config page "/admin/structure/config_pages/types"',
      'field_name' => 'The name of field',
    ],
    'drupal dependencies' => ['config_pages'],
    'aliases' => ['cpgfv'],
  ];

  return $items;
}

/**
 * Call back function drush_config_pages_set_field_value().
 *
 * @var string $bundle
 *    The type of config page.
 * @var string $field_name
 *    The name of field.
 * @var string $value
 *    The value for the field.
 */
function drush_config_pages_set_field_value($bundle, $field_name, $value, $context = NULL) {
  try {
    $config_page = config_pages_config($bundle, $context);

    if (empty($config_page)) {
      $type = ConfigPagesType::load($bundle);
      $config_page = ConfigPages::create([
        'type' => $bundle,
        'label' => $type->label(),
        'context' => $type->getContextData(),
      ]);
      $config_page->save();
    }

    $append = drush_get_option('append');
    if (isset($append)) {
      $value = $config_page->get($field_name)->getString() . $value;
    }

    $config_page->set($field_name, str_replace('\n', PHP_EOL, $value));
    $config_page->save();

    drush_print('Saved new value for ' . $field_name . ' field.');
  }
  catch (Exception $e) {
    \Drupal::logger('config_pages')->error($e->getMessage());
  }
}

/**
 * Call back function drush_config_pages_get_field_value().
 *
 * @var string $bundle
 *    The type of config page.
 * @var string $field_name
 *    The name of field.
 */
function drush_config_pages_get_field_value($bundle, $field_name, $context = NULL) {
  try {
    $config_page = config_pages_config($bundle, $context);

    if (!empty($config_page)) {
      drush_print($config_page->get($field_name)->value);
    }
  }
  catch (Exception $e) {
    \Drupal::logger('config_pages')->error($e->getMessage());
  }
}
