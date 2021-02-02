<?php

/**
 * @file
 * Provide views data for rrssb.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function rrssb_views_data_alter(array &$data) {
  $data['node']['rrssb_buttons'] = [
    'title' => t('RRSSB Buttons'),
    'field' => [
      'title' => t('Ridiculously Responsive Social Share Buttons'),
      'help' => t('Show a social button set.'),
      'id' => 'rrssb_buttons',
    ],
  ];
}
