<?php

/**
 * @file
 * Provides drush commands for Ridiculously Responsive Social Share Buttons.
 *
 * Courtesy of cluke009 at https://drupal.org/node/1231378 .
 */

/**
 * Implements hook_drush_command().
 */
function rrssb_drush_command() {
  $items['rrssb-plugin'] = [
    'description' => dt("Downloads the Ridiculously Responsive Social Share Buttons Plus library from Github."),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'aliases' => ['rrssbdl'],
  ];

  $items['rrssb-gen-css'] = [
    'description' => dt("Regenerate rrssb.buttons.css in RRSSB+ library."),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function rrssb_drush_help($section) {
  switch ($section) {
    case 'drush:rrssb-plugin':
      return dt("Downloads the Ridiculously Responsive Social Share Buttons library from Github.");
  }
}

/**
 * Implements drush_MODULE_post_pm_enable().
 */
function drush_rrssb_post_pm_enable() {
  $modules = func_get_args();
  if (in_array('rrssb', $modules)) {
    drush_rrssb_plugin(NULL, TRUE);
  }
}

/**
 * Callback for the drush command to download the RRSSB+ library.
 */
function drush_rrssb_plugin($only_if_missing = FALSE) {
  // The zip extracts to create a temporary directory which we then rename.
  // Delete existing files, including temporary ones if we failed part way
  // through.
  $library = rrssb_library_path();
  $destination = dirname($library);

  if (is_dir($library)) {
    if ($only_if_missing) {
      return;
    }
    drush_log(dt('Deleting existing RRSSB+ library'), 'notice');
    drush_delete_dir($library, TRUE);
  }

  // Use the git API to find the latest library version.
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/AdamPS/rrssb-plus/releases/latest');
  curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
  $info = json_decode(curl_exec($ch));
  curl_close($ch);

  $zip = drush_download_file($info->zipball_url);
  if (!$zip) {
    return drush_set_error(dt('Failed to download @url', ['@url' => $info->zipball_url]));
  }

  $listing = drush_tarball_extract($zip, $destination, TRUE);
  if (!$listing) {
    return drush_set_error(dt('Failed to extract to @dest', ['@dest' => $destination]));
  }
  unlink($zip);

  // Find the file extracted.
  // Workaround a drush bug where the first line is junk.
  do {
    $temp_library = $destination . '/' . array_shift($listing);
  } while (substr($temp_library, -1) != '/');

  // Move to the correct location.
  drush_move_dir($temp_library, $library, TRUE);

  if (!file_exists("$library/js/rrssb.min.js")) {
    return(drush_set_error(dt('Drush was unable to download the RRSSB+ library to @path', ['@path' => $library])));
  }

  drush_log(dt('The RRSSB+ library has been downloaded to @path', ['@path' => $library]), 'success');
}

/**
 * Implementation of drush_hook_COMMAND().
 */
function drush_rrssb_gen_css() {
  $css = "/* RRSSB+ per-button CSS. */
/* Not required when using the Drupal CMS integration module, which generates its own file. */
/* Generated from Drupal CMS module using 'drush rrssb-gen-css' */
";
  $css .= rrssb_calc_css(rrssb_button_config());
  $target = rrssb_library_path() . '/css/rrssb.buttons.css';
  file_put_contents($target, $css);
}
