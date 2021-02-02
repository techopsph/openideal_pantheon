<?php

namespace Drupal\ckeditor_mentions\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "mentions" plugin.
 *
 * @CKEditorPlugin(
 *   id = "autocomplete",
 *   label = @Translation("Autocomplete"),
 *   module = "ckeditor_mention"
 * )
 */
class Autocomplete extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/ckeditor/plugins/autocomplete/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['textwatcher'];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

}
