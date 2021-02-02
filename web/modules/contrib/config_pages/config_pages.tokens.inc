<?php

/**
 * @file
 * Builds placeholder replacement tokens for configration pages.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\config_pages\Entity\ConfigPages;

/**
 * Implements hook_token_info().
 */
function config_pages_token_info() {

  $types = [];
  // Use the singular 'config_page' otherwise tokens are auto-generated
  // based on the entity.
  $types['config_page'] = [
    'name' => t("ConfigPages"),
    'description' => t("Tokens for ConfigPages."),
  ];

  $tokens = [];

  // Dynamically generate the tokens for all exposed to tokens ConfigPages.
  $query = \Drupal::entityTypeManager()->getStorage('config_pages_type')->getQuery();
  $ids = $query
    ->sort('id')
    ->condition('token', TRUE)
    ->execute();
  $config_pages = ConfigPages::loadMultiple($ids);
  if ($config_pages) {
    foreach ($config_pages as $config_page) {
      $values = $config_page->toArray();
      $type = $values['type'][0]['target_id'];
      $tokens['config_page'][$type] = [
        'name' => $config_page->label(),
        'description' => t('Field values from the config page.'),
        'type' => 'config_pages',
      ];
    }
  }

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function config_pages_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type != 'config_page') {
    return $replacements;
  }
  foreach ($tokens as $name => $original) {
    $parts = explode(':', $name);

    // First argument is always the type of config_pages.
    $config_page_name = array_shift($parts);
    $config_page = config_pages_config($config_page_name);

    if ($entity_tokens = \Drupal::token()->findWithPrefix($tokens, $config_page_name)) {
      $replacements += \Drupal::token()->generate('config_pages', $entity_tokens, ['config_pages' => $config_page], $options, $bubbleable_metadata);
    }
  }
  return $replacements;

}
